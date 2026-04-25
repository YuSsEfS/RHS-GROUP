<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\AnalyzeCvMatchWithAiJob;
use App\Jobs\ScoreRecruitmentRequestMatchesJob;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\CvMatch;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\RecruitmentRequest;
use App\Services\OpenAiRecruitmentService;
use App\Services\RecruitmentRequestDocxImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

    public function store(
        Request $request,
        OpenAiRecruitmentService $ai
    ) {
        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');

        $input = $request->all();

        foreach ([
            'reference',
            'client_name',
            'request_date',
            'position_title',
            'work_location',
            'work_locations',
            'recruitment_reason',
            'age',
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

        $validator = \Validator::make($input, [
            'source_client_request_id' => ['nullable', 'integer', 'exists:recruitment_requests,id'],
            'job_offer_id' => ['nullable', 'exists:job_offers,id'],
            'cv_folder_id' => ['nullable', 'exists:cv_folders,id'],

            'reference' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'request_date' => ['nullable', 'string', 'max:255'],
            'position_title' => ['required', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:500'],
            'work_locations' => ['nullable', 'string', 'max:500'],
            'recruitment_reason' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'string', 'max:100'],
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

        $validated['lang_ar'] = $request->boolean('lang_ar');
        $validated['lang_fr'] = $request->boolean('lang_fr');
        $validated['lang_en'] = $request->boolean('lang_en');
        $validated['lang_es'] = $request->boolean('lang_es');

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

        try {
            $normalized = $ai->normalizeRequest($validated);
        } catch (\Throwable $e) {
            $normalized = [];
        }

        if (!is_array($normalized)) {
            $normalized = [];
        }

        $normalized = array_merge([
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
                $validated['lang_fr'] ? 'français' : null,
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
        ], $normalized);

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
            $sourceClientRequest->admin_seen_at = now();
            $sourceClientRequest->save();

            $recruitmentRequest = $sourceClientRequest;
        } else {
            $recruitmentRequest = RecruitmentRequest::create($createData);
        }
        Bus::dispatchSync(new ScoreRecruitmentRequestMatchesJob(
            recruitmentRequestId: $recruitmentRequest->id,
            folderId: $selectedFolderId,
        ));

        return redirect()
            ->route('admin.recruitment_requests.results', [
                'recruitmentRequest' => $recruitmentRequest->id,
                'offer' => $recruitmentRequest->job_offer_id ?: 'all',
                'folder' => $selectedFolderId ?: 'all',
            ])
            ->with('success', 'Matching terminé avec score local. Vous pouvez lancer l’analyse IA candidat par candidat.');
    }

    public function results(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        $offerId = $request->get('offer');
        $folderId = $request->get('folder');

        if ($offerId && $offerId !== 'all') {
            $targetRequest = RecruitmentRequest::query()
                ->where('job_offer_id', (int) $offerId)
                ->latest('id')
                ->first();

            if ($targetRequest && (int) $targetRequest->id !== (int) $recruitmentRequest->id) {
                return redirect()->route('admin.recruitment_requests.results', [
                    'recruitmentRequest' => $targetRequest->id,
                    'offer' => $offerId,
                    'folder' => $folderId ?: 'all',
                ]);
            }
        }

        $matchesQuery = $recruitmentRequest->matches()
            ->with('cv')
            ->orderByDesc('score');

        if ($folderId && $folderId !== 'all') {
            $matchesQuery->whereHas('cv', function ($query) use ($folderId) {
                $query->where('cv_folder_id', (int) $folderId);
            });
        }

        $matches = $matchesQuery->get();

        $offers = JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $folders = CvFolder::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.recruitment_requests.results', compact(
            'recruitmentRequest',
            'matches',
            'offers',
            'folders',
            'offerId',
            'folderId'
        ));
    }

    public function analyzeWithAi(
        Request $request,
        CvMatch $match
    ) {
        $result = Bus::dispatchSync(new AnalyzeCvMatchWithAiJob($match->id));
        $match->loadMissing('recruitmentRequest');
        $recruitmentRequest = $match->recruitmentRequest;

        if (!$recruitmentRequest) {
            return back()->with('error', $result['message'] ?? 'Match introuvable.');
        }

        return redirect()
            ->route('admin.recruitment_requests.results', [
                'recruitmentRequest' => $recruitmentRequest->id,
                'offer' => $recruitmentRequest->job_offer_id ?: 'all',
                'folder' => request('folder', 'all'),
            ])
            ->with(($result['success'] ?? false) ? 'success' : 'error', $result['message'] ?? 'Analyse indisponible.');
    }

    public function toggleSelection(Request $request, CvMatch $match)
    {
        $match->update([
            'selected' => $request->boolean('selected'),
        ]);

        return back()->with('success', 'Sélection mise à jour.');
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

        if (preg_match('/(\d{1,2})\s*(?:-|à|a|to)\s*(\d{1,2})/iu', $norm, $m)) {
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

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:-|à|a|to)\s*(\d+(?:[.,]\d+)?)/iu', $norm, $m)) {
            return (float) str_replace(',', '.', min($m[1], $m[2]));
        }

        if (preg_match('/(?:plus de|min|minimum|au moins)[^\d]*(\d+(?:[.,]\d+)?)/iu', $norm, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        if (preg_match('/\b(\d+(?:[.,]\d+)?)\b/u', $norm, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }

        if (str_contains($norm, 'debutant') || str_contains($norm, 'débutant')) {
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

}
