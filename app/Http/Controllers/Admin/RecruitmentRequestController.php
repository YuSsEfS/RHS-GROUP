<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\CvMatch;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\RecruitmentRequest;
use App\Services\AiFinalCvScoringService;
use App\Services\LocalCvScoringService;
use App\Services\OpenAiRecruitmentService;
use App\Services\RecruitmentRequestDocxImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class RecruitmentRequestController extends Controller
{
    public function create()
    {
        $offers = JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $folders = CvFolder::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.recruitment_requests.create', [
            'request' => null,
            'importedText' => null,
            'offers' => $offers,
            'folders' => $folders,
        ]);
    }

    public function importDocx(Request $request, RecruitmentRequestDocxImporter $importer)
    {
        $request->validate([
            'docx_file' => ['required', 'file', 'mimes:docx'],
        ]);

        $result = $importer->import($request->file('docx_file')->getPathname());

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
        ]);
    }

    public function store(
        Request $request,
        OpenAiRecruitmentService $ai,
        LocalCvScoringService $localScorer,
        AiFinalCvScoringService $aiFinalScorer
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

        $recruitmentRequest = RecruitmentRequest::create($createData);

        $cvs = $this->getTargetCvs(
            $validated['job_offer_id'] ?? null,
            $selectedFolderId
        );

        $localResults = [];

        foreach ($cvs as $cv) {
            $profile = $cv->structured_profile;

            if (is_string($profile)) {
                $decoded = json_decode($profile, true);
                $profile = is_array($decoded) ? $decoded : [];
            }

            if (!is_array($profile)) {
                $profile = [];
            }

            $profile = $this->enrichProfileForScoring($cv, $profile);

            $scoreData = $localScorer->score($normalized, $profile);

            $localResults[] = [
                'cv' => $cv,
                'profile' => $profile,
                'scoreData' => $scoreData,
            ];
        }

        usort($localResults, function ($a, $b) {
            return ($b['scoreData']['score'] ?? 0) <=> ($a['scoreData']['score'] ?? 0);
        });

        foreach ($localResults as $item) {
            $cv = $item['cv'];
            $local = $item['scoreData'];
            $final = $this->formatLocalResult($local);

            CvMatch::updateOrCreate(
                [
                    'recruitment_request_id' => $recruitmentRequest->id,
                    'cv_id' => $cv->id,
                ],
                [
                    'score' => $final['score'] ?? 0,
                    'score_breakdown' => $final['breakdown'] ?? [],
                    'summary' => $final['summary'] ?? '',
                    'selected' => false,
                ]
            );
        }

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
        CvMatch $match,
        LocalCvScoringService $localScorer,
        AiFinalCvScoringService $aiFinalScorer
    ) {
        $match->loadMissing(['recruitmentRequest', 'cv']);

        $recruitmentRequest = $match->recruitmentRequest;
        $cv = $match->cv;

        if (!$recruitmentRequest || !$cv) {
            return back()->with('error', 'Match introuvable.');
        }

        $requirements = $recruitmentRequest->ai_normalized_requirements;

        if (is_string($requirements)) {
            $decoded = json_decode($requirements, true);
            $requirements = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($requirements) || empty($requirements)) {
            return redirect()
                ->route('admin.recruitment_requests.results', $recruitmentRequest)
                ->with('error', 'Les exigences du poste ne sont pas disponibles.');
        }

        $profile = $cv->structured_profile;

        if (is_string($profile)) {
            $decoded = json_decode($profile, true);
            $profile = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($profile) || empty($profile)) {
            return redirect()
                ->route('admin.recruitment_requests.results', $recruitmentRequest)
                ->with('error', 'Le profil structuré du CV est introuvable.');
        }

        $profile = $this->enrichProfileForScoring($cv, $profile);

        $local = $localScorer->score($requirements, $profile);

        usleep(1500000);

        $final = $aiFinalScorer->score(
            $requirements,
            $profile,
            (float) ($local['score'] ?? 0),
            (array) ($local['breakdown'] ?? []),
            (string) ($local['summary'] ?? '')
        );

        $matchingIa = isset($final['ai_score']) && $final['ai_score'] !== null
            ? round((float) $final['ai_score'], 2)
            : null;

        $newBreakdown = array_merge(
            is_array($final['breakdown'] ?? null) ? $final['breakdown'] : [],
            [
                '_meta' => [
                    'local_score' => round((float) ($final['local_score'] ?? $local['score'] ?? 0), 2),
                    'ai_score' => $matchingIa,
                    'final_score' => round((float) ($final['score'] ?? $match->score), 2),
                    'ai_available' => (bool) ($final['ai_available'] ?? false),
                    'last_analysis' => now()->format('Y-m-d H:i:s'),
                ],
            ]
        );

        $summary = $final['summary'] ?? $match->summary;

        $match->update([
            'score' => $final['score'] ?? $match->score,
            'score_breakdown' => $newBreakdown,
            'summary' => $summary,
        ]);

        return redirect()
            ->route('admin.recruitment_requests.results', [
                'recruitmentRequest' => $recruitmentRequest->id,
                'offer' => $recruitmentRequest->job_offer_id ?: 'all',
                'folder' => request('folder', 'all'),
            ])
            ->with('success', ($final['ai_available'] ?? false)
                ? 'Analyse IA effectuée avec succès. Le score de matching a été mis à jour.'
                : 'OpenAI est temporairement limité. Un matching avancé estimé localement a été appliqué avec succès.');
    }

    public function toggleSelection(Request $request, CvMatch $match)
    {
        $match->update([
            'selected' => $request->boolean('selected'),
        ]);

        return back()->with('success', 'Sélection mise à jour.');
    }

    private function getTargetCvs($jobOfferId = null, ?int $folderId = null)
{
    $cvIds = collect();

    /*
    |--------------------------------------------------------------------------
    | 1. Add CVs from selected folder
    |--------------------------------------------------------------------------
    */
    if ($folderId && Schema::hasColumn('cvs', 'cv_folder_id')) {
        $folderCvIds = Cv::query()
            ->where('cv_folder_id', $folderId)
            ->pluck('id');

        $cvIds = $cvIds->merge($folderCvIds);
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Add CVs from selected offer applications
    |--------------------------------------------------------------------------
    */
    if (!empty($jobOfferId)) {
        $applications = JobApplication::query()
            ->where('job_offer_id', (int) $jobOfferId)
            ->whereNotNull('cv_path')
            ->get();

        foreach ($applications as $application) {
            $relativePath = ltrim((string) $application->cv_path, '/');

            if ($relativePath === '') {
                continue;
            }

            if (!Storage::disk('public')->exists($relativePath)) {
                continue;
            }

            $binary = Storage::disk('public')->get($relativePath);
            $hash = hash('sha256', $binary);

            $existingCv = Cv::query()
                ->where('file_hash', $hash)
                ->first();

            if ($existingCv) {
                $cvIds->push($existingCv->id);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 3. If no offer and no folder selected, use all indexed CVs
    |--------------------------------------------------------------------------
    */
    $query = Cv::query()
        ->whereNotNull('structured_profile');

    if ($cvIds->isNotEmpty()) {
        $query->whereIn('id', $cvIds->unique()->values()->all());
    } elseif (!empty($jobOfferId) || !empty($folderId)) {
        $query->whereRaw('1 = 0');
    }

    return $query->orderByDesc('id')->get();
}

    private function enrichProfileForScoring(Cv $cv, array $profile): array
    {
        if (empty($profile['full_name']) && !empty($cv->candidate_name)) {
            $profile['full_name'] = $cv->candidate_name;
        }

        if (empty($profile['email']) && !empty($cv->email)) {
            $profile['email'] = $cv->email;
        }

        if (empty($profile['phone']) && !empty($cv->phone)) {
            $profile['phone'] = $cv->phone;
        }

        if (empty($profile['city']) && !empty($cv->city)) {
            $profile['city'] = $cv->city;
        }

        if (empty($profile['title']) && !empty($cv->current_title)) {
            $profile['title'] = $cv->current_title;
        }

        $text = (string) (
            $cv->encrypted_extracted_text
            ?? data_get($profile, 'summary')
            ?? ''
        );

        if (empty($profile['years_experience'])) {
            $profile['years_experience'] = $this->estimateExperienceFromText($text);
        }

        if (empty($profile['age'])) {
            $profile['age'] = $this->estimateAgeFromText($text);
        }

        return $profile;
    }

    private function estimateExperienceFromText(?string $text): ?float
    {
        $text = $this->normalizeWhitespace((string) $text);

        if ($text === '') {
            return null;
        }

        $norm = mb_strtolower($text, 'UTF-8');

        $best = null;

        if (preg_match_all('/(\d{4})\s*[-–—]\s*(\d{4}|present|presentement|présent|actuel|aujourd)/iu', $norm, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $start = (int) $match[1];
                $endRaw = $match[2];

                $end = preg_match('/^\d{4}$/', $endRaw)
                    ? (int) $endRaw
                    : (int) date('Y');

                if ($start >= 1970 && $start <= (int) date('Y') && $end >= $start) {
                    $years = min(40, $end - $start);
                    $best = max($best ?? 0, $years);
                }
            }
        }

        if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?:ans|annees|années|year|years)\s+(?:d[’\'e ]experience|experience|expérience)/iu', $norm, $matches)) {
            foreach ($matches[1] as $value) {
                $best = max($best ?? 0, (float) str_replace(',', '.', $value));
            }
        }

        return $best;
    }

    private function estimateAgeFromText(?string $text): ?int
    {
        $text = $this->normalizeWhitespace((string) $text);

        if ($text === '') {
            return null;
        }

        if (preg_match('/\b(\d{1,2})\s*(?:ans|years old|year old)\b/iu', $text, $m)) {
            $age = (int) $m[1];

            if ($age >= 16 && $age <= 70) {
                return $age;
            }
        }

        if (preg_match('/(?:né|nee|née|naissance|born).*?(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/iu', $text, $m)) {
            $year = (int) $m[3];

            if ($year >= 1950 && $year <= (int) date('Y')) {
                return (int) date('Y') - $year;
            }
        }

        if (preg_match('/\b(19[5-9]\d|20[0-1]\d)\b/u', $text, $m)) {
            $year = (int) $m[1];
            $age = (int) date('Y') - $year;

            if ($age >= 16 && $age <= 70) {
                return $age;
            }
        }

        return null;
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

    private function formatLocalResult(array $local): array
    {
        return [
            'score' => (float) ($local['score'] ?? 0),
            'breakdown' => [
                'title_fit' => (float) (($local['breakdown']['title'] ?? 0)),
                'education_fit' => (float) (($local['breakdown']['education'] ?? 0)),
                'experience_fit' => (float) (($local['breakdown']['experience'] ?? 0)),
                'skills_fit' => round(
                    (float) (($local['breakdown']['must_have_skills'] ?? 0)) +
                    (float) (($local['breakdown']['nice_to_have_skills'] ?? 0)),
                    2
                ),
                'language_fit' => (float) (($local['breakdown']['languages'] ?? 0)),
                'location_fit' => (float) (($local['breakdown']['location'] ?? 0)),
                'availability_fit' => (float) (($local['breakdown']['availability'] ?? 0)),
                'overall_consistency' => round(
                    (float) (($local['breakdown']['soft_skills'] ?? 0)) +
                    (float) (($local['breakdown']['consistency_bonus'] ?? 0)),
                    2
                ),
                '_meta' => [
                    'local_score' => round((float) ($local['score'] ?? 0), 2),
                    'ai_score' => null,
                    'final_score' => round((float) ($local['score'] ?? 0), 2),
                    'ai_available' => false,
                    'last_analysis' => null,
                ],
            ],
            'summary' => (string) ($local['summary'] ?? 'Évaluation locale effectuée.'),
        ];
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

    private function syncExistingApplicationsToCvBank(OpenAiRecruitmentService $ai): void
    {
        $applications = JobApplication::query()
            ->whereNotNull('cv_path')
            ->get();

        foreach ($applications as $application) {
            $relativePath = ltrim($application->cv_path, '/');

            if (!Storage::disk('public')->exists($relativePath)) {
                continue;
            }

            $binary = Storage::disk('public')->get($relativePath);
            $hash = hash('sha256', $binary);

            if (Cv::where('file_hash', $hash)->exists()) {
                continue;
            }

            $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
            $tempPath = storage_path('app/temp_' . uniqid() . '.' . $extension);

            file_put_contents($tempPath, $binary);

            try {
                $text = $this->extractTextFromFile($tempPath, $extension);
                $profile = $ai->structureCv($text);

                $storedPath = 'private/cvs/' . uniqid() . '.' . $extension;
                Storage::disk('local')->put($storedPath, $binary);

                Cv::create([
                    'candidate_name' => $profile['full_name'] ?? $application->full_name ?? null,
                    'email' => $profile['email'] ?? $application->email ?? null,
                    'phone' => $profile['phone'] ?? $application->phone ?? null,
                    'original_filename' => basename($relativePath),
                    'mime_type' => $this->guessMimeTypeFromExtension($extension),
                    'file_size' => strlen($binary),
                    'encrypted_path' => $storedPath,
                    'encrypted_extracted_text' => $text,
                    'structured_profile' => $profile,
                    'file_hash' => $hash,
                    'uploaded_at' => now(),
                ]);
            } catch (\Throwable $e) {
                //
            } finally {
                @unlink($tempPath);
            }
        }
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
        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }
}