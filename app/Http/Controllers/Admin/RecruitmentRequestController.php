<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\AnalyzeCvMatchWithAiJob;
use App\Jobs\ScoreRecruitmentRequestMatchesJob;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\CvMatch;
use App\Models\JobOffer;
use App\Models\RecruitmentRequest;
use App\Services\ProcessingEtaService;
use App\Services\RecruitmentRequestDocxImporter;
use App\Services\MatchingProgressService;
use App\Services\MatchingWorkerLauncher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RecruitmentRequestController extends Controller
{
    public function create(Request $request)
    {
        $sourceClientRequest = null;
        $requestData = null;

        if ($request->filled('client_request')) {
            $sourceClientRequest = RecruitmentRequest::query()
                ->whereKey((int) $request->integer('client_request'))
                ->whereNotNull('client_user_id')
                ->first();

            abort_unless($sourceClientRequest, 404);
            $requestData = $sourceClientRequest;
        }

        $offers = JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $folders = CvFolder::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.recruitment_requests.create', [
            'request' => $requestData,
            'importedText' => null,
            'offers' => $offers,
            'folders' => $folders,
            'sourceClientRequest' => $sourceClientRequest,
        ]);
    }

    public function importDocx(Request $request, RecruitmentRequestDocxImporter $importer)
    {
        $request->validate([
            'docx_file' => ['required', 'file', 'mimes:docx'],
            'source_client_request_id' => ['nullable', 'integer', 'exists:recruitment_requests,id'],
        ]);

        $result = $importer->import($request->file('docx_file')->getPathname());
        $sourceClientRequest = null;

        if ($request->filled('source_client_request_id')) {
            $sourceClientRequest = RecruitmentRequest::query()
                ->whereKey((int) $request->integer('source_client_request_id'))
                ->whereNotNull('client_user_id')
                ->first();
        }

        $offers = JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $folders = CvFolder::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.recruitment_requests.create', [
            'request' => (object) ($result['mapped'] ?? []),
            'importedText' => $result['raw_text'] ?? null,
            'offers' => $offers,
            'folders' => $folders,
            'sourceClientRequest' => $sourceClientRequest,
        ]);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        foreach ([
            'reference',
            'client_name',
            'logo',
            'request_date',
            'position_title',
            'work_location',
            'work_locations',
            'recruitment_reason',
            'age',
            'candidate_count',
            'gender',
            'education',
            'experience_years',
            'availability',
            'other_language',
            'budget_type',
            'monthly_salary',
            'contract_type',
            'planned_start_date',
            'missions',
            'personal_qualities',
            'specific_knowledge',
            'other_benefits',
        ] as $field) {
            if (isset($input[$field]) && is_array($input[$field])) {
                $input[$field] = $this->flattenToString($input[$field], ', ');
            }
        }

        foreach ([
            'reference',
            'client_name',
            'request_date',
            'position_title',
            'work_location',
            'work_locations',
            'recruitment_reason',
            'age',
            'candidate_count',
            'gender',
            'education',
            'experience_years',
            'availability',
            'other_language',
            'budget_type',
            'monthly_salary',
            'contract_type',
            'planned_start_date',
            'missions',
            'personal_qualities',
            'specific_knowledge',
            'other_benefits',
        ] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = $this->normalizeWhitespace($input[$field]);
            }
        }

        if (empty($input['work_location']) && !empty($input['work_locations'])) {
            $input['work_location'] = $input['work_locations'];
        }

        if (!empty($input['job_offer_ids']) && is_array($input['job_offer_ids'])) {
            $input['job_offer_id'] = collect($input['job_offer_ids'])->filter()->first();
        }

        if (!empty($input['cv_folder_ids']) && is_array($input['cv_folder_ids'])) {
            $input['cv_folder_id'] = collect($input['cv_folder_ids'])->filter()->first();
        }

        $validator = \Validator::make($input, [
            'source_client_request_id' => ['nullable', 'integer', 'exists:recruitment_requests,id'],
            'job_offer_id' => ['nullable', 'exists:job_offers,id'],
            'cv_folder_id' => ['nullable', 'exists:cv_folders,id'],
            'job_offer_ids' => ['nullable', 'array'],
            'job_offer_ids.*' => ['nullable', 'exists:job_offers,id'],
            'cv_folder_ids' => ['nullable', 'array'],
            'cv_folder_ids.*' => ['nullable', 'exists:cv_folders,id'],

            'reference' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'request_date' => ['nullable', 'string', 'max:255'],
            'position_title' => ['required', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:500'],
            'work_locations' => ['nullable', 'string', 'max:500'],
            'recruitment_reason' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'string', 'max:100'],
            'candidate_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'gender' => ['nullable', 'string', 'max:20'],
            'education' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'string', 'max:255'],
            'availability' => ['nullable', 'string', 'max:255'],
            'other_language' => ['nullable', 'string', 'max:255'],
            'budget_type' => ['nullable', 'string', 'max:255'],
            'monthly_salary' => ['nullable', 'string', 'max:255'],
            'contract_type' => ['nullable', 'string', 'max:255'],
            'planned_start_date' => ['nullable', 'string', 'max:255'],
            'missions' => ['nullable', 'string'],
            'personal_qualities' => ['nullable', 'string'],
            'specific_knowledge' => ['nullable', 'string'],
            'other_benefits' => ['nullable', 'string'],
            'lang_ar' => ['nullable'],
            'lang_fr' => ['nullable'],
            'lang_en' => ['nullable'],
            'lang_es' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $selectedOfferIds = collect($input['job_offer_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedOfferIds->isEmpty() && !empty($validated['job_offer_id'])) {
            $selectedOfferIds = collect([(int) $validated['job_offer_id']]);
        }

        $selectedFolderIds = collect($input['cv_folder_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedFolderIds->isEmpty()) {
            $selectedFolderIds = collect([!empty($validated['cv_folder_id']) ? (int) $validated['cv_folder_id'] : null]);
        }

        $validated['lang_ar'] = $request->boolean('lang_ar');
        $validated['lang_fr'] = $request->boolean('lang_fr');
        $validated['lang_en'] = $request->boolean('lang_en');
        $validated['lang_es'] = $request->boolean('lang_es');

        unset($validated['logo']);
        unset($validated['job_offer_ids'], $validated['cv_folder_ids']);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('recruitment-requests', 'public');
        }

        $sourceClientRequestId = !empty($validated['source_client_request_id'])
            ? (int) $validated['source_client_request_id']
            : null;

        unset($validated['source_client_request_id']);

        $selectedFolderId = !empty($validated['cv_folder_id'])
            ? (int) $validated['cv_folder_id']
            : null;

        if (!empty($validated['job_offer_id'])) {
            $offer = JobOffer::find($validated['job_offer_id']);

            if ($offer && empty($validated['position_title'])) {
                $validated['position_title'] = $offer->title;
            }
        }

        $locations = $this->parseMultiValue($validated['work_location'] ?? '');

        $normalized = [
            'role' => $validated['position_title'] ?? '',
            'must_have_skills' => $this->explodeFreeText($validated['specific_knowledge'] ?? ''),
            'nice_to_have_skills' => [],
            'education' => $validated['education'] ?? '',
            'min_experience_years' => $this->parseExperienceYears($validated['experience_years'] ?? ''),
            'experience_text' => $validated['experience_years'] ?? '',
            'age_requirement' => $this->parseAgeRequirement($validated['age'] ?? ''),
            'age_text' => $validated['age'] ?? '',
            'languages' => array_values(array_filter([
                $validated['lang_ar'] ? 'arabe' : null,
                $validated['lang_fr'] ? 'francais' : null,
                $validated['lang_en'] ? 'anglais' : null,
                $validated['lang_es'] ? 'espagnol' : null,
                $validated['other_language'] ?? null,
            ])),
            'location' => implode(', ', $locations),
            'locations' => $locations,
            'availability' => $validated['availability'] ?? '',
            'contract_type' => $validated['contract_type'] ?? '',
            'soft_skills' => $this->explodeFreeText($validated['personal_qualities'] ?? ''),
            'mission_keywords' => $this->explodeFreeText($validated['missions'] ?? ''),
            'cv_folder_id' => $selectedFolderId,
            'job_offer_ids' => $selectedOfferIds->all(),
            'cv_folder_ids' => $selectedFolderIds->filter()->values()->all(),
        ];

        if (empty($normalized['location'])) {
            $normalized['location'] = implode(', ', $locations);
        }

        if (empty($normalized['locations'])) {
            $normalized['locations'] = $locations;
        }

        $normalized['min_experience_years'] = $this->parseExperienceYears(
            $normalized['min_experience_years'] ?? ($validated['experience_years'] ?? '')
        );

        $normalized['age_requirement'] = $this->parseAgeRequirement(
            is_array($normalized['age_requirement'] ?? null)
                ? ($validated['age'] ?? '')
                : ($normalized['age_requirement'] ?? ($validated['age'] ?? ''))
        );

        $validated['ai_normalized_requirements'] = $normalized;

        $createData = $validated;

        if (!Schema::hasColumn('recruitment_requests', 'cv_folder_id')) {
            unset($createData['cv_folder_id']);
        }

        if (!Schema::hasColumn('recruitment_requests', 'work_locations')) {
            unset($createData['work_locations']);
        }

        if (!Schema::hasColumn('recruitment_requests', 'logo_path')) {
            unset($createData['logo_path']);
        }

        if (!Schema::hasColumn('recruitment_requests', 'candidate_count')) {
            unset($createData['candidate_count']);
        }

        $sourceClientRequest = null;

        if ($sourceClientRequestId) {
            $sourceClientRequest = RecruitmentRequest::query()
                ->whereKey($sourceClientRequestId)
                ->whereNotNull('client_user_id')
                ->first();
        }

        if ($sourceClientRequest) {
            $sourceClientRequest->fill($createData);
            $sourceClientRequest->request_status = RecruitmentRequest::STATUS_MATCHING_IN_PROGRESS;
            $sourceClientRequest->pipeline_stage = RecruitmentRequest::PIPELINE_STAGE_MATCHING;
            $sourceClientRequest->admin_seen_at = now();
            $sourceClientRequest->save();
            $sourceClientRequest->markMatchingQueued();

            $recruitmentRequest = $sourceClientRequest;
        } else {
            $createData['pipeline_stage'] = RecruitmentRequest::PIPELINE_STAGE_MATCHING;
            $recruitmentRequest = RecruitmentRequest::create($createData);
            $recruitmentRequest->markMatchingQueued();
        }

        ScoreRecruitmentRequestMatchesJob::dispatch(
            recruitmentRequestId: $recruitmentRequest->id,
            folderId: $selectedFolderId,
        )->afterCommit();
        DB::afterCommit(fn () => app(MatchingWorkerLauncher::class)->start(
            ScoreRecruitmentRequestMatchesJob::queueNameFor($recruitmentRequest->id)
        ));

        return redirect()
            ->route('admin.recruitment_requests.results', [
                'recruitmentRequest' => $recruitmentRequest->id,
                'offer' => $recruitmentRequest->job_offer_id ?: 'all',
                'folder' => $selectedFolderId ?: 'all',
            ])
            ->with('success', 'Le matching combine a ete lance en arriere-plan. Les resultats apparaitront ici des la fin du traitement.');
    }

    public function results(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        $offerId = $request->get('offer');
        $folderId = $request->get('folder');
        $search = $this->normalizeWhitespace((string) $request->query('q', ''));

        $recruitmentRequest->loadMissing('jobOffer:id,title');
        $recruitmentRequest->loadCount('matches');
        $recruitmentRequest->syncResolvedMatchingStateIfNeeded();
        $recruitmentRequest->refresh();
        $resolvedMatchingStatus = $recruitmentRequest->resolveMatchingStatus()
            ?? RecruitmentRequest::MATCHING_STATUS_PENDING;

        if (
            $resolvedMatchingStatus === RecruitmentRequest::MATCHING_STATUS_COMPLETED
            && (
                !$recruitmentRequest->matching_viewed_at
                || (
                    $recruitmentRequest->matching_finished_at
                    && $recruitmentRequest->matching_finished_at->gt($recruitmentRequest->matching_viewed_at)
                )
            )
        ) {
            $recruitmentRequest->markMatchingViewed();
        }

        $recruitmentRequest->refresh();
        $resolvedMatchingStatus = $recruitmentRequest->resolveMatchingStatus()
            ?? RecruitmentRequest::MATCHING_STATUS_PENDING;

        $baseMatchesQuery = $recruitmentRequest->matches()
            ->orderByDesc('score');

        if ($folderId && $folderId !== 'all') {
            $baseMatchesQuery->whereIn('cv_id', Cv::query()
                ->select('id')
                ->where('cv_folder_id', (int) $folderId));
        }

        $this->applyMatchSearch($baseMatchesQuery, $search);

        $matchCounts = Cache::remember(implode(':', [
            'admin.matching.results.counts.v2',
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

        $matches = $resolvedMatchingStatus === RecruitmentRequest::MATCHING_STATUS_COMPLETED
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

        $offers = Cache::remember('admin.job_offers.options.v1', now()->addSeconds(30), fn () => JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get());

        $folders = Cache::remember('admin.cv_folders.options.v1', now()->addSeconds(30), fn () => CvFolder::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get());
        $matchingProgress = $this->lightMatchingProgressPayload(
            $recruitmentRequest,
            $resolvedMatchingStatus,
            $matchesTotal
        );
        $odooExportStatus = Cache::get(OdooPreselectionController::exportStatusCacheKey($recruitmentRequest->id));

        return view('admin.recruitment_requests.results', compact(
            'recruitmentRequest',
            'matches',
            'offers',
            'folders',
            'offerId',
            'folderId',
            'matchingProgress',
            'matchesTotal',
            'selectedMatchesCount',
            'odooExportStatus',
            'search'
        ));
    }

    public function matchSuggestions(Request $request, RecruitmentRequest $recruitmentRequest)
    {
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

        $items = $query
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
            ->values();

        return response()->json($items);
    }

    public function matchingStatus(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        $recruitmentRequest->loadCount('matches');
        $recruitmentRequest->syncResolvedMatchingStateIfNeeded();
        $recruitmentRequest->refresh();

        return response()->json($this->lightMatchingProgressPayload($recruitmentRequest));
    }

    public function cancelMatching(RecruitmentRequest $recruitmentRequest, MatchingProgressService $progress)
    {
        $status = $recruitmentRequest->resolveMatchingStatus();

        if (!in_array($status, [
            RecruitmentRequest::MATCHING_STATUS_PENDING,
            RecruitmentRequest::MATCHING_STATUS_PROCESSING,
        ], true)) {
            return back()->with('error', 'Ce matching ne peut plus etre annule.');
        }

        $progress->cancel($recruitmentRequest->id);
        $recruitmentRequest->markMatchingCancelled();

        return back()->with('success', 'Le matching a ete annule. Vous pouvez modifier la demande.');
    }

    public function analyzeWithAi(Request $request, CvMatch $match)
    {
        $match->loadMissing('recruitmentRequest');
        $recruitmentRequest = $match->recruitmentRequest;

        if (!$recruitmentRequest) {
            return back()->with('error', 'Match introuvable.');
        }

        $match->update([
            'ai_analysis_status' => RecruitmentRequest::JOB_STATUS_PENDING,
            'ai_analysis_started_at' => null,
            'ai_analysis_completed_at' => null,
            'ai_analysis_error_message' => null,
        ]);

        AnalyzeCvMatchWithAiJob::dispatch($match->id)->afterCommit();

        return redirect()
            ->route('admin.recruitment_requests.results', [
                'recruitmentRequest' => $recruitmentRequest->id,
                'offer' => $recruitmentRequest->job_offer_id ?: 'all',
                'folder' => request('folder', 'all'),
            ])
            ->with('success', 'L analyse IA a ete planifiee en arriere-plan.');
    }

    public function toggleSelection(Request $request, CvMatch $match)
    {
        $match->update([
            'selected' => $request->boolean('selected'),
        ]);

        return back()->with('success', 'Selection mise a jour.');
    }

    private function parseAgeRequirement(?string $value): array
    {
        $value = $this->normalizeWhitespace((string) $value);

        if ($value === '') {
            return [
                'min' => null,
                'max' => null,
                'text' => '',
            ];
        }

        $norm = mb_strtolower($value, 'UTF-8');

        if (preg_match('/(\d{1,2})\s*(?:-|a|to)\s*(\d{1,2})/iu', $norm, $m)) {
            return [
                'min' => (int) min($m[1], $m[2]),
                'max' => (int) max($m[1], $m[2]),
                'text' => $value,
            ];
        }

        if (preg_match('/(?:moins de|max|maximum|jusqu)[^\d]*(\d{1,2})/iu', $norm, $m)) {
            return [
                'min' => null,
                'max' => (int) $m[1],
                'text' => $value,
            ];
        }

        if (preg_match('/(?:plus de|min|minimum|au moins)[^\d]*(\d{1,2})/iu', $norm, $m)) {
            return [
                'min' => (int) $m[1],
                'max' => null,
                'text' => $value,
            ];
        }

        if (preg_match('/\b(\d{1,2})\b/u', $norm, $m)) {
            return [
                'min' => null,
                'max' => (int) $m[1],
                'text' => $value,
            ];
        }

        return [
            'min' => null,
            'max' => null,
            'text' => $value,
        ];
    }

    private function parseExperienceYears($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = $this->normalizeWhitespace((string) $value);

        if ($value === '') {
            return null;
        }

        $norm = mb_strtolower($value, 'UTF-8');

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:-|a|to)\s*(\d+(?:[.,]\d+)?)/iu', $norm, $m)) {
            return (float) str_replace(',', '.', min($m[1], $m[2]));
        }

        if (preg_match('/(?:plus de|min|minimum|au moins)[^\d]*(\d+(?:[.,]\d+)?)/iu', $norm, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        if (preg_match('/\b(\d+(?:[.,]\d+)?)\b/u', $norm, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        if (str_contains($norm, 'debutant')) {
            return 0;
        }

        return null;
    }

    private function parseMultiValue(?string $value): array
    {
        $value = $this->normalizeWhitespace((string) $value);

        if ($value === '') {
            return [];
        }

        $parts = preg_split('/[,;|\/\n]+/u', $value);
        $parts = array_map(fn ($item) => trim($item), $parts);

        return array_values(array_unique(array_filter($parts)));
    }

    private function explodeFreeText(string $text): array
    {
        $parts = preg_split('/[,;\n\-•]+/u', $text);
        $parts = array_map(fn ($v) => trim((string) $v), $parts);

        return array_values(array_filter($parts));
    }

    private function flattenToString(array $value, string $separator = ' '): string
    {
        $flat = [];

        array_walk_recursive($value, function ($item) use (&$flat) {
            if ($item !== null && $item !== '') {
                $flat[] = (string) $item;
            }
        });

        return trim(implode($separator, $flat));
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

    private function lightMatchingProgressPayload(
        RecruitmentRequest $recruitmentRequest,
        ?string $matchingStatus = null,
        ?int $matchesCount = null
    ): array {
        $matchingStatus ??= $recruitmentRequest->resolveMatchingStatus()
            ?? RecruitmentRequest::MATCHING_STATUS_PENDING;
        $matchesCount ??= (int) ($recruitmentRequest->matches_count ?? 0);
        $isCompleted = $matchingStatus === RecruitmentRequest::MATCHING_STATUS_COMPLETED;
        $cached = app(MatchingProgressService::class)->payload($recruitmentRequest->id);
        $processed = (int) ($cached['processed'] ?? ($isCompleted ? $matchesCount : 0));
        $total = (int) ($cached['total'] ?? ($isCompleted ? max(1, $matchesCount) : 0));
        $liveMatches = (int) ($cached['matches'] ?? $matchesCount);
        $remaining = max(0, $total - $processed);
        $percentage = $total > 0 ? (int) min(100, round(($processed / $total) * 100)) : ($isCompleted ? 100 : 0);

        return [
            'status' => $matchingStatus,
            'status_label' => RecruitmentRequest::availableMatchingStatuses()[$matchingStatus] ?? ucfirst($matchingStatus),
            'matches_count' => max($matchesCount, $liveMatches),
            'processed_items' => $processed,
            'total_items' => $total,
            'remaining_items' => $remaining,
            'progress_percentage' => $percentage,
            'estimated_time_remaining' => $isCompleted ? 'Termine' : ($total > 0 ? $remaining . ' CV restants' : 'Calcul en cours'),
            'error_message' => $recruitmentRequest->resolveMatchingError(),
            'queued_jobs' => $this->queuedMatchingJobsCount($recruitmentRequest),
            'status_message' => match ($matchingStatus) {
                RecruitmentRequest::MATCHING_STATUS_COMPLETED => 'Matching termine. Les resultats sont disponibles.',
                RecruitmentRequest::MATCHING_STATUS_FAILED => 'Matching echoue. Consultez le message d erreur puis relancez si necessaire.',
                RecruitmentRequest::MATCHING_STATUS_CANCELLED => 'Matching annule. Vous pouvez modifier la demande.',
                RecruitmentRequest::MATCHING_STATUS_PROCESSING => 'Matching en cours. La progression est mise a jour pendant l analyse.',
                default => 'Matching en attente de traitement.',
            },
        ];
    }

    private function matchingProgressPayload(
        RecruitmentRequest $recruitmentRequest,
        ProcessingEtaService $eta,
        mixed $folderId = null
    ): array {
        $matchingStatus = $recruitmentRequest->resolveMatchingStatus()
            ?? RecruitmentRequest::MATCHING_STATUS_PENDING;
        $matchesCount = (int) ($recruitmentRequest->matches_count ?? $recruitmentRequest->matches()->count());
        $finishedStatus = in_array($matchingStatus, [
            RecruitmentRequest::MATCHING_STATUS_COMPLETED,
            RecruitmentRequest::MATCHING_STATUS_FAILED,
        ], true);
        $totalCandidates = $finishedStatus
            ? max(1, $matchesCount)
            : $this->estimateMatchingTotalCandidates($recruitmentRequest, $folderId);

        if ($matchesCount > $totalCandidates) {
            $totalCandidates = $matchesCount;
        }

        $processed = $finishedStatus
            ? $totalCandidates
            : min($matchesCount, $totalCandidates);

        $recent = ['processed' => 0, 'window_seconds' => 600];
        $queuedJobs = 0;
        $payload = $eta->payload(
            processed: $processed,
            total: $totalCandidates,
            startedAt: $recruitmentRequest->matching_started_at ?: $recruitmentRequest->created_at,
            status: $matchingStatus,
            recentProcessed: $recent['processed'],
            recentWindowSeconds: $recent['window_seconds'],
            preferRecent: false
        );

        if ($matchingStatus === RecruitmentRequest::MATCHING_STATUS_COMPLETED) {
            $payload['progress_percentage'] = 100;
            $payload['estimated_seconds_remaining'] = 0;
            $payload['estimated_time_remaining'] = 'Termine';
        }

        $statusMessage = match (true) {
            $matchingStatus === RecruitmentRequest::MATCHING_STATUS_COMPLETED => 'Matching termine. Les resultats sont disponibles.',
            $matchingStatus === RecruitmentRequest::MATCHING_STATUS_FAILED => 'Matching echoue. Consultez le message d erreur puis relancez si necessaire.',
            $matchingStatus === RecruitmentRequest::MATCHING_STATUS_PROCESSING => 'Matching en cours, progression chargee sans verification lourde de la file.',
            default => 'Matching en attente de traitement.',
        };

        return $payload + [
            'status' => $matchingStatus,
            'status_label' => RecruitmentRequest::availableMatchingStatuses()[$matchingStatus] ?? ucfirst($matchingStatus),
            'matches_count' => $matchesCount,
            'error_message' => $recruitmentRequest->resolveMatchingError(),
            'queued_jobs' => $queuedJobs,
            'status_message' => $statusMessage,
        ];
    }

    private function recentMatchingThroughput(RecruitmentRequest $recruitmentRequest): array
    {
        $minutes = 10;
        $since = now()->subMinutes($minutes);
        $query = CvMatch::query()
            ->where('recruitment_request_id', $recruitmentRequest->id)
            ->where('updated_at', '>=', $since);

        $processed = (int) (clone $query)->count();
        $firstRecent = (clone $query)->min('updated_at');
        $windowSeconds = $firstRecent
            ? max(60, \Illuminate\Support\Carbon::parse($firstRecent)->diffInSeconds(now(), true))
            : $minutes * 60;

        return [
            'processed' => $processed,
            'window_seconds' => $windowSeconds,
        ];
    }

    private function queuedMatchingJobsCount(RecruitmentRequest $recruitmentRequest): int
    {
        if (!Schema::hasTable('jobs')) {
            return 0;
        }

        return DB::table('jobs')
            ->where('queue', ScoreRecruitmentRequestMatchesJob::queueNameFor($recruitmentRequest->id))
            ->where('payload', 'like', '%ScoreRecruitmentRequestMatchesJob%')
            ->where('payload', 'like', '%' . $recruitmentRequest->id . '%')
            ->count();
    }

    private function estimateMatchingTotalCandidates(RecruitmentRequest $recruitmentRequest, mixed $folderId = null): int
    {
        $folderId = $folderId && $folderId !== 'all'
            ? (int) $folderId
            : ((int) ($recruitmentRequest->cv_folder_id ?? 0) ?: null);

        return Cache::remember(
            'admin.matching.total-candidates.v2:' . ($folderId ?: 'all'),
            now()->addSeconds(60),
            function () use ($folderId) {
                $query = Cv::query()->whereNotNull('structured_profile');

                if (Schema::hasColumn('cvs', 'archived_at')) {
                    $query->whereNull('archived_at');
                }

                if (Schema::hasColumn('cvs', 'is_active')) {
                    $query->where('is_active', true);
                }

                if ($folderId && Schema::hasColumn('cvs', 'cv_folder_id')) {
                    $query->where('cv_folder_id', $folderId);
                }

                return $query->count();
            }
        );
    }
}
