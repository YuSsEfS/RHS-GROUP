<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\CvStorageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CvBankController extends Controller
{
    use ResolvesUserTimezone;

    public function index(Request $request)
    {
        $this->authorizeCvAccess($request->user());
        $timezone = $this->userTimezone($request);

        $q = trim((string) $request->query('q', ''));

        $cvs = Cv::query()
            ->with('folder:id,name')
            ->withCount('matches')
            ->whereNull('archived_at')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('candidate_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('current_title', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%");
                });
            })
            ->latest('uploaded_at')
            ->paginate(30)
            ->through(fn (Cv $cv) => $this->payload($cv, $timezone));

        return response()->json($cvs);
    }

    public function show(Request $request, Cv $cv)
    {
        $this->authorizeCvAccess($request->user());

        $cv->load('folder:id,name', 'matches.recruitmentRequest:id,reference,position_title');
        $cv->loadCount('matches');

        return response()->json([
            ...$this->payload($cv, $this->userTimezone($request)),
            'notes' => $cv->notes,
            'structured_profile' => $cv->structured_profile,
            'matches' => $cv->matches
                ->sortByDesc('score')
                ->take(10)
                ->map(fn ($match) => [
                    'id' => $match->id,
                    'score' => round((float) $match->score, 1),
                    'selected' => (bool) $match->selected,
                    'summary' => $match->summary,
                    'request' => $match->recruitmentRequest ? [
                        'id' => $match->recruitmentRequest->id,
                        'reference' => $match->recruitmentRequest->reference,
                        'title' => $match->recruitmentRequest->position_title,
                    ] : null,
                ])
                ->values(),
        ]);
    }

    public function download(Request $request, Cv $cv, CvStorageOptimizationService $storageOptimization)
    {
        $this->authorizeCvAccess($request->user());

        $filename = $cv->original_filename ?: ('cv-' . $cv->id);
        $mime = $cv->mime_type ?: 'application/octet-stream';

        if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
            return Storage::disk('local')->download($cv->encrypted_path, $filename, [
                'Content-Type' => $mime,
            ]);
        }

        if (
            Schema::hasColumn('cvs', 'source_type') &&
            Schema::hasColumn('cvs', 'source_id') &&
            $cv->source_type === 'application' &&
            !empty($cv->source_id)
        ) {
            $application = JobApplication::find($cv->source_id);
            $relativePath = ltrim((string) ($application?->cv_path ?: ''), '/');

            if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
                return Storage::disk('public')->download($relativePath, basename($relativePath), [
                    'Content-Type' => $mime,
                ]);
            }
        }

        $binary = $storageOptimization->readBinary($cv);

        if ($binary !== null) {
            return response($binary, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
            ]);
        }

        abort(404, 'CV file not found.');
    }

    private function authorizeCvAccess(User $user): void
    {
        abort_unless($user->isAdmin() || $user->hasAnyPermission(['cv_bank', 'cv_bank_manage']), 403);
    }

    private function payload(Cv $cv, string $timezone): array
    {
        return [
            'id' => $cv->id,
            'candidate_name' => $cv->candidate_name ?: 'Candidat',
            'title' => $cv->candidate_name ?: 'Candidat',
            'current_title' => $cv->current_title,
            'email' => $cv->email,
            'phone' => $cv->phone,
            'city' => $cv->city,
            'source' => $cv->display_source,
            'folder' => $cv->folder?->name,
            'matches_count' => (int) ($cv->matches_count ?? 0),
            'filename' => $cv->original_filename,
            'can_open' => !empty($cv->encrypted_path) || !empty($cv->source_id) || !empty($cv->compressed_path),
            'compression_status' => Cv::availableCompressionStatuses()[$cv->compression_status] ?? $cv->compression_status,
            'uploaded_at' => $this->localDate($cv->uploaded_at ?: $cv->created_at, $timezone),
            'timezone' => $timezone,
        ];
    }
}
