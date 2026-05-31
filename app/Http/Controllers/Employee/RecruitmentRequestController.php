<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Jobs\ScoreRecruitmentRequestMatchesJob;
use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\CvMatch;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\RecruitmentRequest;
use App\Services\CvStorageOptimizationService;
use App\Services\MatchingWorkerLauncher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use ZipArchive;

class RecruitmentRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $canUpdateRecruitmentAssignments = $user->hasPermission('recruitment_assignments_update');
        $status = (string) $request->query('status', 'all');
        $stage = (string) $request->query('stage', 'all');

        $requests = RecruitmentRequest::query()
            ->with([
                'clientUser',
                'clientAlerts' => fn ($query) => $query->latest()->limit(3),
            ])
            ->withCount('clientAlerts')
            ->where('assigned_employee_id', $user->id)
            ->whereNotNull('client_user_id')
            ->when($status !== 'all', fn ($query) => $query->where('assignment_status', $status))
            ->when($stage !== 'all', fn ($query) => $query->where('pipeline_stage', $stage))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        RecruitmentRequest::query()
            ->where('assigned_employee_id', $user->id)
            ->whereNotNull('client_user_id')
            ->whereNull('assignment_seen_at')
            ->update(['assignment_seen_at' => now()]);

        return view('employee.recruitment_requests.index', [
            'user' => $user,
            'requests' => $requests,
            'status' => $status,
            'stage' => $stage,
            'assignmentStatuses' => RecruitmentRequest::availableAssignmentStatuses(),
            'pipelineStages' => RecruitmentRequest::availablePipelineStages(),
            'requestStatuses' => RecruitmentRequest::availableStatuses(),
            'canUpdateRecruitmentAssignments' => $canUpdateRecruitmentAssignments,
        ]);
    }

    public function show(RecruitmentRequest $recruitmentRequest)
    {
        abort_unless($recruitmentRequest->isAssignedTo(auth()->user()), 403);

        return view('employee.recruitment_requests.show', [
            'user' => auth()->user(),
            'requestItem' => $recruitmentRequest->load([
                'clientUser',
                'clientAlerts.responder',
                'matches.cv.folder',
            ])->loadCount(['matches', 'clientAlerts']),
            'assignmentStatuses' => RecruitmentRequest::availableAssignmentStatuses(),
            'pipelineStages' => RecruitmentRequest::availablePipelineStages(),
            'requestStatuses' => RecruitmentRequest::availableStatuses(),
            'canUpdateRecruitmentAssignments' => auth()->user()->hasPermission('recruitment_assignments_update'),
        ]);
    }

    public function results(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        abort_unless($recruitmentRequest->isAssignedTo(auth()->user()), 403);

        $offerId = $request->get('offer');
        $folderId = $request->get('folder');
        $search = $this->normalizeWhitespace((string) $request->query('q', ''));

        $recruitmentRequest->loadMissing('jobOffer:id,title');
        $recruitmentRequest->loadCount('matches');
        $recruitmentRequest->syncResolvedMatchingStateIfNeeded();
        $recruitmentRequest->refresh();

        $baseMatchesQuery = $recruitmentRequest->matches()
            ->orderByDesc('score');

        if ($folderId && $folderId !== 'all') {
            $baseMatchesQuery->whereIn('cv_id', Cv::query()
                ->select('id')
                ->where('cv_folder_id', (int) $folderId));
        }

        $this->applyMatchSearch($baseMatchesQuery, $search);

        $matchCounts = Cache::remember(implode(':', [
            'employee.matching.results.counts.v2',
            $recruitmentRequest->id,
            $folderId ?: 'all',
            $search !== '' ? md5($search) : 'no-search',
            optional($recruitmentRequest->matching_finished_at ?: $recruitmentRequest->updated_at)->timestamp,
        ]), now()->addSeconds(5), function () use ($baseMatchesQuery) {
            return [
                'total' => (clone $baseMatchesQuery)->count(),
                'selected' => (clone $baseMatchesQuery)->where('selected', true)->count(),
            ];
        });

        $matchesTotal = (int) ($matchCounts['total'] ?? 0);
        $selectedMatchesCount = (int) ($matchCounts['selected'] ?? 0);

        $matches = $recruitmentRequest->resolveMatchingStatus() === RecruitmentRequest::MATCHING_STATUS_COMPLETED
            ? (clone $baseMatchesQuery)
                ->select([
                    'id',
                    'recruitment_request_id',
                    'cv_id',
                    'score',
                    'score_breakdown',
                    'summary',
                    'selected',
                    'ai_analysis_status',
                ])
                ->with([
                    'cv:id,candidate_name,phone,email,cv_folder_id,original_filename,mime_type,encrypted_path,compressed_path,compression_verified_at',
                    'cv.folder:id,name',
                ])
                ->simplePaginate(25)
                ->withQueryString()
            : collect();

        $offers = Cache::remember('employee.job_offers.options.v1', now()->addSeconds(60), fn () => JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get());

        $folders = Cache::remember('employee.cv_folders.options.v1', now()->addSeconds(60), fn () => CvFolder::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get());

        return view('employee.recruitment_requests.results', compact(
            'recruitmentRequest',
            'matches',
            'offers',
            'folders',
            'offerId',
            'folderId',
            'matchesTotal',
            'selectedMatchesCount',
            'search'
        ));
    }

    public function matchSuggestions(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        abort_unless($recruitmentRequest->isAssignedTo(auth()->user()), 403);

        $search = $this->normalizeWhitespace((string) $request->query('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $folderId = $request->query('folder');
        $query = $recruitmentRequest->matches()
            ->orderByDesc('score');

        if ($folderId && $folderId !== 'all') {
            $query->whereIn('cv_id', Cv::query()
                ->select('id')
                ->where('cv_folder_id', (int) $folderId));
        }

        $this->applyMatchSearch($query, $search);

        return response()->json($query
            ->select(['id', 'cv_id', 'score'])
            ->with(['cv:id,candidate_name,email,phone,original_filename,cv_folder_id', 'cv.folder:id,name'])
            ->limit(8)
            ->get()
            ->map(function (CvMatch $match) {
                $cv = $match->cv;

                return [
                    'id' => $match->id,
                    'title' => $cv?->candidate_name ?: 'Candidat inconnu',
                    'value' => $cv?->candidate_name ?: ($cv?->email ?: $cv?->phone),
                    'meta' => collect([
                        $cv?->email,
                        $cv?->phone,
                        $cv?->folder?->name,
                        'Score ' . number_format((float) $match->score, 0) . '%',
                    ])->filter()->implode(' - '),
                ];
            })
            ->values());
    }

    public function launchMatching(RecruitmentRequest $recruitmentRequest)
    {
        abort_unless($recruitmentRequest->isAssignedTo(auth()->user()), 403);
        abort_unless(auth()->user()->hasPermission('recruitment_assignments_update'), 403);

        $recruitmentRequest->markMatchingQueued();
        $recruitmentRequest->forceFill([
            'request_status' => RecruitmentRequest::STATUS_MATCHING_IN_PROGRESS,
            'pipeline_stage' => RecruitmentRequest::PIPELINE_STAGE_MATCHING,
        ])->save();

        ScoreRecruitmentRequestMatchesJob::dispatch(
            recruitmentRequestId: $recruitmentRequest->id,
            folderId: $recruitmentRequest->cv_folder_id,
        )->afterCommit();
        \Illuminate\Support\Facades\DB::afterCommit(fn () => app(MatchingWorkerLauncher::class)->start(
            ScoreRecruitmentRequestMatchesJob::queueNameFor($recruitmentRequest->id)
        ));

        return redirect()
            ->route('employee.recruitment-requests.show', $recruitmentRequest)
            ->with('success', 'Le matching a ete lance en arriere-plan pour cette demande assignee.');
    }

    public function update(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        abort_unless($recruitmentRequest->isAssignedTo(auth()->user()), 403);
        abort_unless(auth()->user()->hasPermission('recruitment_assignments_update'), 403);

        $validated = $request->validate([
            'assignment_status' => ['required', Rule::in(array_keys(RecruitmentRequest::availableAssignmentStatuses()))],
            'pipeline_stage' => ['required', Rule::in(array_keys(RecruitmentRequest::availablePipelineStages()))],
            'employee_notes' => ['nullable', 'string'],
        ]);

        $recruitmentRequest->update([
            'assignment_status' => $validated['assignment_status'],
            'pipeline_stage' => $validated['pipeline_stage'],
            'employee_notes' => $validated['employee_notes'] ?? null,
        ]);

        return redirect()
            ->route('employee.recruitment-requests.show', $recruitmentRequest)
            ->with('success', 'La progression de votre demande assignee a ete mise a jour.');
    }

    public function openMatchCv(RecruitmentRequest $recruitmentRequest, CvMatch $match, CvStorageOptimizationService $storageOptimization)
    {
        $cv = $this->resolveAuthorizedMatchCv($recruitmentRequest, $match);
        $mime = $cv->mime_type ?: $this->guessMimeTypeFromExtension(pathinfo((string) $cv->original_filename, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'application/pdf') || str_starts_with($mime, 'image/')) {
            return view('employee.recruitment_requests.viewer', [
                'cv' => $cv,
                'streamUrl' => route('employee.recruitment-requests.matches.stream', [
                    'recruitmentRequest' => $recruitmentRequest,
                    'match' => $match,
                ]),
                'mime' => $mime,
            ]);
        }

        return $this->streamAuthorizedCv($cv, $storageOptimization);
    }

    public function streamMatchCv(RecruitmentRequest $recruitmentRequest, CvMatch $match, CvStorageOptimizationService $storageOptimization)
    {
        $cv = $this->resolveAuthorizedMatchCv($recruitmentRequest, $match);

        return $this->streamAuthorizedCv($cv, $storageOptimization);
    }

    public function downloadSelected(
        Request $request,
        RecruitmentRequest $recruitmentRequest,
        CvStorageOptimizationService $storageOptimization
    ) {
        abort_unless($recruitmentRequest->isAssignedTo(auth()->user()), 403);

        if ($request->isMethod('post')) {
            $visibleIds = collect($request->input('visible_matches', []))
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();
            $selectedIds = collect($request->input('selected_matches', []))
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();

            if ($visibleIds->isNotEmpty()) {
                $recruitmentRequest->matches()
                    ->whereIn('id', $visibleIds->all())
                    ->update(['selected' => false]);
            }

            if ($selectedIds->isNotEmpty()) {
                $recruitmentRequest->matches()
                    ->whereIn('id', $selectedIds->all())
                    ->update(['selected' => true]);
            }
        }

        $matches = $recruitmentRequest
            ->matches()
            ->where('selected', true)
            ->with('cv')
            ->get();

        if ($matches->isEmpty()) {
            return back()->with('error', 'Aucun CV selectionne.');
        }

        $tempFolder = storage_path('app/temp');

        if (!is_dir($tempFolder)) {
            mkdir($tempFolder, 0777, true);
        }

        $zipFilename = 'selected-cvs-request-' . $recruitmentRequest->id . '-' . now()->format('Ymd_His') . '.zip';
        $zipPath = $tempFolder . DIRECTORY_SEPARATOR . $zipFilename;
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Impossible de creer le fichier ZIP.');
        }

        $addedFiles = 0;

        foreach ($matches as $match) {
            if (!$match->cv) {
                continue;
            }

            $cv = $match->cv;
            $binary = $storageOptimization->readBinary($cv);

            if ($binary === null) {
                continue;
            }

            $extension = pathinfo((string) ($cv->original_filename ?: 'cv'), PATHINFO_EXTENSION);
            $baseName = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($cv->candidate_name ?: 'cv'));
            $safeFilename = trim($baseName, '-') . '-' . $cv->id . ($extension ? '.' . $extension : '');

            $zip->addFromString($safeFilename, $binary);
            $addedFiles++;
        }

        $zip->close();

        if ($addedFiles === 0 || !file_exists($zipPath)) {
            return back()->with('error', 'Aucun fichier valide trouve pour telechargement.');
        }

        return response()
            ->download($zipPath)
            ->deleteFileAfterSend(true);
    }

    private function resolveAuthorizedMatchCv(RecruitmentRequest $recruitmentRequest, CvMatch $match): Cv
    {
        abort_unless($recruitmentRequest->isAssignedTo(auth()->user()), 403);
        abort_unless((int) $match->recruitment_request_id === (int) $recruitmentRequest->id, 404);

        $match->loadMissing('cv');
        abort_unless($match->cv, 404);

        return $match->cv;
    }

    private function streamAuthorizedCv(Cv $cv, CvStorageOptimizationService $storageOptimization)
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

        $binary = $storageOptimization->readBinary($cv);

        if ($binary !== null) {
            $filename = $cv->original_filename ?: ('cv-' . $cv->id);
            $mime = $cv->mime_type ?: 'application/octet-stream';

            return response($binary, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
            ]);
        }

        abort(404, 'CV file not found.');
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

    private function normalizeWhitespace(?string $value): string
    {
        $value = (string) $value;
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    private function applyMatchSearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $terms = collect(preg_split('/\s+/u', $search) ?: [])
            ->map(fn ($term) => trim((string) $term))
            ->filter(fn ($term) => $term !== '')
            ->take(6)
            ->values();

        foreach ($terms as $term) {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';

            $query->where(function ($matchQuery) use ($like) {
                $matchQuery
                    ->where('summary', 'like', $like)
                    ->orWhereHas('cv', function ($cvQuery) use ($like) {
                        $cvQuery
                            ->where('candidate_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('city', 'like', $like)
                            ->orWhere('current_title', 'like', $like)
                            ->orWhere('original_filename', 'like', $like);
                    });
            });
        }
    }
}
