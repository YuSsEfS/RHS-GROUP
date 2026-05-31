<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\JobApplication;
use App\Services\CvStorageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CvBankController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $folder = trim((string) $request->query('folder', 'all'));
        $source = trim((string) $request->query('source', 'all'));

        $query = Cv::query()
            ->select($this->cvIndexColumns())
            ->with('folder:id,name')
            ->when(Schema::hasColumn('cvs', 'archived_at'), fn ($builder) => $builder->whereNull('archived_at'))
            ->when(Schema::hasColumn('cvs', 'is_active'), fn ($builder) => $builder->where('is_active', true))
            ->when($q !== '', function ($builder) use ($q) {
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
            })
            ->when($folder !== '' && $folder !== 'all' && Schema::hasColumn('cvs', 'cv_folder_id'), function ($builder) use ($folder) {
                $builder->where('cv_folder_id', (int) $folder);
            })
            ->when($source !== '' && $source !== 'all' && Schema::hasColumn('cvs', 'source_type'), function ($builder) use ($source) {
                $builder->where('source_type', $source);
            });

        if (Schema::hasColumn('cvs', 'uploaded_at')) {
            $query->latest('uploaded_at')->latest('id');
        } else {
            $query->latest('id');
        }

        $hasActiveFilters = $q !== ''
            || !in_array($folder, ['', 'all'], true)
            || !in_array($source, ['', 'all'], true);

        $cvs = $query->simplePaginate(30)->withQueryString();

        $cvListTotal = $hasActiveFilters
            ? $cvs->count() . '+'
            : Cache::remember('employee.cvs.active.count.v1', now()->addSeconds(60), function () {
                return Cv::query()
                    ->when(Schema::hasColumn('cvs', 'archived_at'), fn ($builder) => $builder->whereNull('archived_at'))
                    ->when(Schema::hasColumn('cvs', 'is_active'), fn ($builder) => $builder->where('is_active', true))
                    ->count();
            });

        return view('employee.cvs.index', [
            'cvs' => $cvs,
            'folders' => Cache::remember('employee.cv_folders.options.v1', now()->addSeconds(60), fn () => CvFolder::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()),
            'cvListTotal' => $cvListTotal,
            'q' => $q,
            'folder' => $folder,
            'source' => $source,
            'canManageCvBank' => auth()->user()->hasPermission('cv_bank_manage'),
        ]);
    }

    public function open(Cv $cv, CvStorageOptimizationService $storageOptimization)
    {
        $this->authorizeCvAccess();

        $mime = $cv->mime_type ?: $this->guessMimeTypeFromExtension(pathinfo((string) $cv->original_filename, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'application/pdf') || str_starts_with($mime, 'image/')) {
            return view('employee.cvs.viewer', [
                'cv' => $cv,
                'streamUrl' => route('employee.cvs.stream', $cv),
                'mime' => $mime,
            ]);
        }

        return $this->stream($cv, $storageOptimization);
    }

    public function stream(Cv $cv, CvStorageOptimizationService $storageOptimization)
    {
        $this->authorizeCvAccess();

        if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
            return response()->file(Storage::disk('local')->path($cv->encrypted_path), [
                'Content-Type' => $cv->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . addslashes($cv->original_filename ?: ('cv-' . $cv->id)) . '"',
            ]);
        }

        if (
            Schema::hasColumn('cvs', 'source_type')
            && Schema::hasColumn('cvs', 'source_id')
            && $cv->source_type === 'application'
            && !empty($cv->source_id)
        ) {
            $application = JobApplication::find($cv->source_id);
            $relativePath = ltrim((string) ($application?->cv_path ?? ''), '/');

            if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
                return response()->file(Storage::disk('public')->path($relativePath), [
                    'Content-Type' => $cv->mime_type ?: $this->guessMimeTypeFromExtension(pathinfo($relativePath, PATHINFO_EXTENSION)),
                    'Content-Disposition' => 'inline; filename="' . addslashes(basename($relativePath)) . '"',
                ]);
            }
        }

        $binary = $storageOptimization->readBinary($cv);

        abort_unless($binary !== null, 404, 'CV introuvable.');

        return response($binary, 200, [
            'Content-Type' => $cv->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($cv->original_filename ?: ('cv-' . $cv->id)) . '"',
        ]);
    }

    private function authorizeCvAccess(): void
    {
        abort_unless(auth()->user()?->hasAnyPermission(['cv_bank', 'cv_bank_manage']), 403);
    }

    private function cvIndexColumns(): array
    {
        $baseColumns = [
            'id',
            'candidate_name',
            'email',
            'phone',
            'original_filename',
            'file_size',
            'uploaded_at',
            'source_type',
            'source_id',
            'mime_type',
            'encrypted_path',
            'cv_folder_id',
            'current_title',
            'city',
            'is_active',
            'archived_at',
        ];

        return array_values(array_filter(
            $baseColumns,
            fn (string $column) => Schema::hasColumn('cvs', $column)
        ));
    }

    private function guessMimeTypeFromExtension(?string $extension): string
    {
        return match (strtolower((string) $extension)) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
