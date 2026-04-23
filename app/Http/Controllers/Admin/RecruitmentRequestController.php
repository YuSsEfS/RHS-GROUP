<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvMatch;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\RecruitmentRequest;
use App\Services\AiFinalCvScoringService;
use App\Services\LocalCvScoringService;
use App\Services\OpenAiRecruitmentService;
use App\Services\RecruitmentRequestDocxImporter;
use Illuminate\Http\Request;
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

        return view('admin.recruitment_requests.create', [
            'request' => null,
            'importedText' => null,
            'offers' => $offers,
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

        return view('admin.recruitment_requests.create', [
            'request' => (object) ($result['mapped'] ?? []),
            'importedText' => $result['raw_text'] ?? null,
            'offers' => $offers,
        ]);
    }

    public function store(
        Request $request,
        OpenAiRecruitmentService $ai,
        LocalCvScoringService $localScorer,
        AiFinalCvScoringService $aiFinalScorer
    ) {
        $input = $request->all();

        foreach ([
            'reference',
            'client_name',
            'request_date',
            'position_title',
            'work_location',
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
                $input[$field] = $this->flattenToString($input[$field]);
            }
        }

        foreach ([
            'reference',
            'client_name',
            'request_date',
            'position_title',
            'work_location',
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

        $validator = \Validator::make($input, [
            'job_offer_id' => ['nullable', 'exists:job_offers,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'request_date' => ['nullable', 'string', 'max:255'],
            'position_title' => ['required', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:255'],
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

        if (!empty($validated['job_offer_id'])) {
            $offer = JobOffer::find($validated['job_offer_id']);

            if ($offer) {
                $validated['position_title'] = $validated['position_title'] ?: $offer->title;
            }
        }

        try {
            $normalized = $ai->normalizeRequest($validated);
        } catch (\Throwable $e) {
            $normalized = [];
        }

        if (empty($normalized)) {
            $normalized = [
                'role' => $validated['position_title'] ?? '',
                'must_have_skills' => $this->explodeFreeText($validated['specific_knowledge'] ?? ''),
                'nice_to_have_skills' => [],
                'education' => $validated['education'] ?? '',
                'min_experience_years' => $validated['experience_years'] ?? '',
                'languages' => array_values(array_filter([
                    $validated['lang_ar'] ? 'arabe' : null,
                    $validated['lang_fr'] ? 'français' : null,
                    $validated['lang_en'] ? 'anglais' : null,
                    $validated['lang_es'] ? 'espagnol' : null,
                    $validated['other_language'] ?? null,
                ])),
                'location' => $validated['work_location'] ?? '',
                'availability' => $validated['availability'] ?? '',
                'contract_type' => $validated['contract_type'] ?? '',
                'soft_skills' => $this->explodeFreeText($validated['personal_qualities'] ?? ''),
                'mission_keywords' => $this->explodeFreeText($validated['missions'] ?? ''),
            ];
        }

        $validated['ai_normalized_requirements'] = $normalized;

        $recruitmentRequest = RecruitmentRequest::create($validated);

        set_time_limit(300);

        if (!empty($validated['job_offer_id'])) {
            $applications = JobApplication::query()
                ->where('job_offer_id', (int) $validated['job_offer_id'])
                ->whereNotNull('cv_path')
                ->get();

            $cvIds = [];

            foreach ($applications as $application) {
                $relativePath = ltrim((string) $application->cv_path, '/');

                if ($relativePath === '') {
                    continue;
                }

                $binary = null;

                if (Storage::disk('public')->exists($relativePath)) {
                    $binary = Storage::disk('public')->get($relativePath);
                }

                if (!$binary) {
                    continue;
                }

                $hash = hash('sha256', $binary);

                $existingCv = Cv::query()
                    ->where('file_hash', $hash)
                    ->first();

                if ($existingCv) {
                    $cvIds[] = $existingCv->id;
                }
            }

            $cvs = Cv::query()
                ->whereIn('id', array_unique($cvIds))
                ->whereNotNull('structured_profile')
                ->orderByDesc('id')
                ->get();
        } else {
            $cvs = Cv::query()
                ->whereNotNull('structured_profile')
                ->orderByDesc('id')
                ->get();
        }

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
            ])
            ->with('success', 'Matching terminé avec score local. Vous pouvez lancer l’analyse IA candidat par candidat.');
    }

    public function results(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        $offerId = $request->get('offer');

        if ($offerId && $offerId !== 'all') {
            $targetRequest = RecruitmentRequest::query()
                ->where('job_offer_id', (int) $offerId)
                ->latest('id')
                ->first();

            if ($targetRequest && (int) $targetRequest->id !== (int) $recruitmentRequest->id) {
                return redirect()->route('admin.recruitment_requests.results', [
                    'recruitmentRequest' => $targetRequest->id,
                    'offer' => $offerId,
                ]);
            }
        }

        $matches = $recruitmentRequest->matches()
            ->with('cv')
            ->orderByDesc('score')
            ->get();

        $offers = JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        return view('admin.recruitment_requests.results', compact(
            'recruitmentRequest',
            'matches',
            'offers'
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

        if (($final['ai_available'] ?? false) === true) {
            return redirect()
                ->route('admin.recruitment_requests.results', [
                    'recruitmentRequest' => $recruitmentRequest->id,
                    'offer' => $recruitmentRequest->job_offer_id ?: 'all',
                ])
                ->with('success', 'Analyse IA effectuée avec succès. Le score de matching a été mis à jour.');
        }

        return redirect()
            ->route('admin.recruitment_requests.results', [
                'recruitmentRequest' => $recruitmentRequest->id,
                'offer' => $recruitmentRequest->job_offer_id ?: 'all',
            ])
            ->with('success', 'OpenAI est temporairement limité. Un matching avancé estimé localement a été appliqué avec succès.');
    }

    public function toggleSelection(Request $request, CvMatch $match)
    {
        $match->update([
            'selected' => $request->boolean('selected'),
        ]);

        return back()->with('success', 'Sélection mise à jour.');
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

    private function flattenToString(array $value): string
    {
        $flat = [];

        array_walk_recursive($value, function ($item) use (&$flat) {
            if ($item !== null && $item !== '') {
                $flat[] = (string) $item;
            }
        });

        return trim(implode(' ', $flat));
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