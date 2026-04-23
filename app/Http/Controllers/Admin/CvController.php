<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\JobApplication;
use App\Services\OpenAiRecruitmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

class CvController extends Controller
{
    public function index()
    {
        $cvs = Cv::latest()->paginate(20);

        return view('admin.cvs.index', compact('cvs'));
    }

    public function create()
    {
        return view('admin.cvs.create');
    }

    public function store(Request $request, OpenAiRecruitmentService $ai)
    {
        $request->validate([
            'cv_files' => ['required', 'array'],
            'cv_files.*' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:10240'],
        ]);

        foreach ($request->file('cv_files') as $file) {
            $binary = file_get_contents($file->getRealPath());
            $hash = hash('sha256', $binary);

            if (Cv::where('file_hash', $hash)->exists()) {
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
            ];

            try {
                $profile = $ai->structureCv($text);
            } catch (\Throwable $e) {
            }

            $storedPath = 'private/cvs/' . uniqid() . '.' . $extension;
            Storage::disk('local')->put($storedPath, $binary);

            Cv::create([
                'candidate_name' => $profile['full_name'] ?? null,
                'email' => $profile['email'] ?? null,
                'phone' => $profile['phone'] ?? null,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'encrypted_path' => $storedPath,
                'encrypted_extracted_text' => $text,
                'structured_profile' => $profile,
                'file_hash' => $hash,
                'uploaded_at' => now(),
            ]);
        }

        return redirect()->route('admin.cvs.index')->with('success', 'CVs uploaded successfully.');
    }

    public function open(Cv $cv)
    {
        /*
        |--------------------------------------------------------------------------
        | 1) Preferred: open copied/private CV from local disk
        |--------------------------------------------------------------------------
        */
        if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
            $fullPath = Storage::disk('local')->path($cv->encrypted_path);
            $filename = $cv->original_filename ?: ('cv-' . $cv->id);
            $mime = $cv->mime_type ?: 'application/octet-stream';

            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2) Fallback: try to find original file from old JobApplication
        |--------------------------------------------------------------------------
        */
        $jobApplication = JobApplication::query()
            ->where(function ($q) use ($cv) {
                if (!empty($cv->email)) {
                    $q->where('email', $cv->email);
                }

                if (!empty($cv->candidate_name)) {
                    $q->orWhere('full_name', $cv->candidate_name);
                }
            })
            ->whereNotNull('cv_path')
            ->latest('id')
            ->first();

        if ($jobApplication && !empty($jobApplication->cv_path)) {
            $relativePath = ltrim($jobApplication->cv_path, '/');

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

        abort(404, 'CV file not found.');
    }

    private function extractTextFromFile(string $filePath, string $extension): string
    {
        if ($extension === 'pdf') {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            return trim($pdf->getText());
        }

        if (in_array($extension, ['doc', 'docx'])) {
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
            return trim(file_get_contents($filePath));
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