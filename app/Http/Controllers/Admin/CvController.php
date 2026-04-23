<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Services\OpenAiRecruitmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class CvController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $source = trim((string) $request->query('source', 'all'));
        $folder = trim((string) $request->query('folder', 'all'));
        $status = trim((string) $request->query('status', 'active'));
        $offer = trim((string) $request->query('offer', 'all'));

        $direction = trim((string) $request->query('direction', 'desc'));

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query = Cv::query();

        if (method_exists(Cv::class, 'folder')) {
            $query->with('folder');
        }

        $query->when($q !== '', function ($builder) use ($q) {
            $builder->where(function ($sub) use ($q) {
                $sub->where('candidate_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('original_filename', 'like', "%{$q}%");

                if (Schema::hasColumn('cvs', 'city')) {
                    $sub->orWhere('city', 'like', "%{$q}%");
                }

                if (Schema::hasColumn('cvs', 'current_title')) {
                    $sub->orWhere('current_title', 'like', "%{$q}%");
                }
            });
        });

        if (Schema::hasColumn('cvs', 'source_type') && $source !== '' && $source !== 'all') {
            $query->where('source_type', $source);
        }

        if (Schema::hasColumn('cvs', 'cv_folder_id') && $folder !== '' && $folder !== 'all') {
            $query->where('cv_folder_id', (int) $folder);
        }

        if (Schema::hasColumn('cvs', 'is_active')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (
            $offer !== '' &&
            $offer !== 'all' &&
            Schema::hasColumn('cvs', 'source_type') &&
            Schema::hasColumn('cvs', 'source_id')
        ) {
            if ($offer === 'spontaneous') {
                $query->where('source_type', 'application')
                    ->whereIn('source_id', function ($sub) {
                        $sub->select('id')
                            ->from('job_applications')
                            ->whereNull('job_offer_id');
                    });
            } else {
                $offerId = (int) $offer;

                $query->where('source_type', 'application')
                    ->whereIn('source_id', function ($sub) use ($offerId) {
                        $sub->select('id')
                            ->from('job_applications')
                            ->where('job_offer_id', $offerId);
                    });
            }
        }

        if (Schema::hasColumn('cvs', 'uploaded_at')) {
            $query->orderBy('uploaded_at', $direction)->orderBy('id', 'desc');
        } else {
            $query->orderBy('id', $direction);
        }

        $cvs = $query->get();

        $folders = class_exists(CvFolder::class)
            ? CvFolder::query()->orderBy('name')->get()
            : collect();

        $offers = JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        return view('admin.cvs.index', compact(
            'cvs',
            'folders',
            'offers',
            'q',
            'source',
            'folder',
            'status',
            'offer',
            'direction'
        ));
    }

    public function create()
    {
        $folders = class_exists(CvFolder::class)
            ? CvFolder::query()->orderBy('name')->get()
            : collect();

        return view('admin.cvs.create', compact('folders'));
    }

    public function store(Request $request, OpenAiRecruitmentService $ai)
    {
        @set_time_limit(900);
        @ini_set('memory_limit', '1024M');

        $rules = [
            'cv_files' => ['required', 'array', 'min:1'],
            'cv_files.*' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:51200'],
            'relative_paths' => ['nullable', 'array'],
            'relative_paths.*' => ['nullable', 'string', 'max:1000'],
            'new_folder_name' => ['nullable', 'string', 'max:255'],
        ];

        if (Schema::hasColumn('cvs', 'cv_folder_id')) {
            $rules['cv_folder_id'] = ['nullable', 'integer', 'exists:cv_folders,id'];
        }

        if (Schema::hasColumn('cvs', 'city')) {
            $rules['city'] = ['nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('cvs', 'current_title')) {
            $rules['current_title'] = ['nullable', 'string', 'max:255'];
        }

        if (Schema::hasColumn('cvs', 'notes')) {
            $rules['notes'] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);

        $targetFolderId = $this->resolveTargetFolderId($request);

        $uploadedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($request->file('cv_files', []) as $file) {
            try {
                $binary = file_get_contents($file->getRealPath());
                $hash = hash('sha256', $binary);

                if (Schema::hasColumn('cvs', 'file_hash') && Cv::where('file_hash', $hash)->exists()) {
                    $skippedCount++;
                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());
                $text = $this->extractTextFromFile($file->getRealPath(), $extension);

                $profile = [
                    'full_name' => null,
                    'email' => null,
                    'phone' => null,
                    'title' => null,
                    'years_experience' => null,
                    'education' => null,
                    'languages' => [],
                    'technical_skills' => [],
                    'soft_skills' => [],
                    'industries' => [],
                    'certifications' => [],
                    'summary' => $text ? mb_substr($text, 0, 1500) : null,
                    'city' => null,
                ];

                try {
                    if (!empty($text)) {
                        $structured = $ai->structureCv($text);

                        if (is_array($structured) && !empty($structured)) {
                            $profile = array_merge($profile, $structured);
                        }
                    }
                } catch (\Throwable $e) {
                    //
                }

                $storedPath = 'private/cvs/' . uniqid('cv_', true) . '.' . $extension;

                Storage::disk('local')->put($storedPath, $binary);

                $resolvedCity = $validated['city'] ?? null;
                $resolvedTitle = $validated['current_title'] ?? null;

                if (!$resolvedCity) {
                    $resolvedCity = $profile['city']
                        ?? data_get($profile, 'location.city')
                        ?? data_get($profile, 'address.city')
                        ?? null;
                }

                if (!$resolvedTitle) {
                    $resolvedTitle = $profile['title']
                        ?? data_get($profile, 'current_title')
                        ?? data_get($profile, 'headline')
                        ?? data_get($profile, 'desired_position')
                        ?? null;
                }

                $data = [
                    'candidate_name' => $profile['full_name'] ?? null,
                    'email' => $profile['email'] ?? null,
                    'phone' => $profile['phone'] ?? null,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'encrypted_path' => $storedPath,
                    'encrypted_extracted_text' => $text,
                    'structured_profile' => $profile,
                    'uploaded_at' => now(),
                ];

                if (Schema::hasColumn('cvs', 'file_hash')) {
                    $data['file_hash'] = $hash;
                }

                if (Schema::hasColumn('cvs', 'source_type')) {
                    $data['source_type'] = 'manual';
                }

                if (Schema::hasColumn('cvs', 'source_id')) {
                    $data['source_id'] = null;
                }

                if (Schema::hasColumn('cvs', 'cv_folder_id')) {
                    $data['cv_folder_id'] = $targetFolderId;
                }

                if (Schema::hasColumn('cvs', 'city')) {
                    $data['city'] = $resolvedCity;
                }

                if (Schema::hasColumn('cvs', 'current_title')) {
                    $data['current_title'] = $resolvedTitle;
                }

                if (Schema::hasColumn('cvs', 'is_active')) {
                    $data['is_active'] = true;
                }

                if (Schema::hasColumn('cvs', 'notes')) {
                    $data['notes'] = $validated['notes'] ?? null;
                }

                Cv::create($data);

                $uploadedCount++;

                unset($binary, $text, $profile, $data);
                gc_collect_cycles();

            } catch (\Throwable $e) {
                $failedCount++;
                report($e);
                continue;
            }
        }

        $message = "{$uploadedCount} CV(s) importé(s) avec succès.";

        if ($skippedCount > 0) {
            $message .= " {$skippedCount} fichier(s) en double ignoré(s).";
        }

        if ($failedCount > 0) {
            $message .= " {$failedCount} fichier(s) échoué(s).";
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'uploaded' => $uploadedCount,
                'skipped' => $skippedCount,
                'failed' => $failedCount,
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', $message);
    }

    public function open(Cv $cv)
    {
        if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
            $fullPath = Storage::disk('local')->path($cv->encrypted_path);
            $filename = $cv->original_filename ?: ('cv-' . $cv->id);
            $mime = $cv->mime_type ?: 'application/octet-stream';

            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
            ]);
        }

        if (
            Schema::hasColumn('cvs', 'source_type') &&
            Schema::hasColumn('cvs', 'source_id') &&
            $cv->source_type === 'application' &&
            !empty($cv->source_id)
        ) {
            $application = JobApplication::find($cv->source_id);

            if ($application && !empty($application->cv_path)) {
                $relativePath = ltrim($application->cv_path, '/');

                if (Storage::disk('public')->exists($relativePath)) {
                    $fullPath = Storage::disk('public')->path($relativePath);
                    $filename = basename($relativePath);
                    $mime = $cv->mime_type ?: $this->guessMimeTypeFromExtension(pathinfo($relativePath, PATHINFO_EXTENSION));

                    return response()->file($fullPath, [
                        'Content-Type' => $mime,
                        'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
                    ]);
                }
            }
        }

        abort(404, 'CV file not found.');
    }

    public function assignFolder(Request $request, Cv $cv)
    {
        $validated = $request->validate([
            'cv_folder_id' => ['nullable', 'integer', 'exists:cv_folders,id'],
        ]);

        if (Schema::hasColumn('cvs', 'cv_folder_id')) {
            $cv->cv_folder_id = $validated['cv_folder_id'] ?? null;
            $cv->save();
        }

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', 'Dossier du CV mis à jour avec succès.');
    }

    public function destroy(Cv $cv)
    {
        if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
            Storage::disk('local')->delete($cv->encrypted_path);
        }

        $cv->delete();

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', 'CV supprimé avec succès.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'cv_ids' => ['required', 'array', 'min:1'],
            'cv_ids.*' => ['integer', 'exists:cvs,id'],
        ]);

        $cvs = Cv::whereIn('id', $validated['cv_ids'])->get();

        foreach ($cvs as $cv) {
            if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
                Storage::disk('local')->delete($cv->encrypted_path);
            }

            $cv->delete();
        }

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', $cvs->count() . ' CV supprimé(s) avec succès.');
    }

    private function resolveTargetFolderId(Request $request): ?int
    {
        if (!class_exists(CvFolder::class)) {
            return null;
        }

        $existingFolderId = $request->filled('cv_folder_id')
            ? (int) $request->input('cv_folder_id')
            : null;

        $newFolderName = trim((string) $request->input('new_folder_name', ''));

        if ($newFolderName !== '') {
            $folder = CvFolder::firstOrCreate(
                ['slug' => Str::slug($newFolderName)],
                [
                    'name' => $newFolderName,
                    'description' => null,
                    'created_by' => optional(auth()->user())->id,
                ]
            );

            return (int) $folder->id;
        }

        if ($existingFolderId) {
            return $existingFolderId;
        }

        $uploadedDirName = $this->extractTopDirectoryNameFromUpload($request);

        if ($uploadedDirName) {
            $folder = CvFolder::firstOrCreate(
                ['slug' => Str::slug($uploadedDirName)],
                [
                    'name' => $uploadedDirName,
                    'description' => null,
                    'created_by' => optional(auth()->user())->id,
                ]
            );

            return (int) $folder->id;
        }

        return null;
    }

    private function extractTopDirectoryNameFromUpload(Request $request): ?string
    {
        $relativePaths = $request->input('relative_paths', []);

        if (!is_array($relativePaths) || empty($relativePaths)) {
            return null;
        }

        foreach ($relativePaths as $path) {
            $path = trim((string) $path);

            if ($path === '') {
                continue;
            }

            $path = str_replace('\\', '/', $path);
            $parts = array_values(array_filter(explode('/', $path)));

            if (count($parts) >= 2) {
                return $parts[0];
            }
        }

        return null;
    }

    private function extractTextFromFile(string $filePath, string $extension): string
    {
        try {
            if ($extension === 'pdf') {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($filePath);
                return trim($pdf->getText());
            }

            if (in_array($extension, ['doc', 'docx'], true)) {
                $phpWord = IOFactory::load($filePath);
                $text = '';

                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        }
                    }
                }

                return trim($text);
            }

            if ($extension === 'txt') {
                return trim((string) file_get_contents($filePath));
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
    }

    private function guessMimeTypeFromExtension(string $extension): string
    {
        $extension = strtolower((string) $extension);

        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }
}