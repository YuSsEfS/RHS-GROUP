<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExportSelectedCvsToOdooJob;
use App\Models\CvMatch;
use App\Models\JobApplication;
use App\Models\RecruitmentRequest;
use App\Services\CvStorageOptimizationService;
use App\Services\OdooService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OdooPreselectionController extends Controller
{
    private const EXPORT_STATUS_TTL_HOURS = 6;

    public function exportSelected(
        Request $request,
        RecruitmentRequest $recruitmentRequest
    ) {
        $recruitmentRequest->loadMissing('jobOffer');

        $autoSelectCount = max(0, min((int) $request->input('auto_select_count', 0), 5000));
        $selectedMatchIds = $autoSelectCount > 0
            ? $recruitmentRequest->matches()
                ->orderByDesc('score')
                ->limit($autoSelectCount)
                ->pluck('id')
                ->all()
            : $request->input('selected_matches', []);

        if (empty($selectedMatchIds)) {
            return back()->with('error', 'Veuillez selectionner au moins un CV.');
        }

        if ($autoSelectCount > 0) {
            $recruitmentRequest->matches()->update(['selected' => false]);
            $recruitmentRequest->matches()
                ->whereIn('id', $selectedMatchIds)
                ->update(['selected' => true]);
        }

        $matches = CvMatch::with('cv')
            ->where('recruitment_request_id', $recruitmentRequest->id)
            ->whereIn('id', $selectedMatchIds)
            ->get();

        if ($matches->isEmpty()) {
            return back()->with('error', 'Aucun CV valide trouve.');
        }

        self::putExportStatus($recruitmentRequest->id, [
            'status' => 'queued',
            'message' => count($selectedMatchIds) . ' CV en attente d envoi vers Odoo.',
            'selected_count' => count($selectedMatchIds),
        ]);

        ExportSelectedCvsToOdooJob::dispatch(
            recruitmentRequestId: $recruitmentRequest->id,
            selectedMatchIds: array_values(array_map('intval', $selectedMatchIds)),
        )->afterCommit();

        return back();
    }

    public function exportStatus(RecruitmentRequest $recruitmentRequest)
    {
        return response()->json(Cache::get(self::exportStatusCacheKey($recruitmentRequest->id)) ?: [
            'status' => 'idle',
            'message' => null,
        ]);
    }

    public static function exportStatusCacheKey(int $recruitmentRequestId): string
    {
        return 'odoo.export.status.' . $recruitmentRequestId;
    }

    public static function putExportStatus(int $recruitmentRequestId, array $payload): void
    {
        Cache::put(
            self::exportStatusCacheKey($recruitmentRequestId),
            array_merge([
                'updated_at' => now()->toIso8601String(),
            ], $payload),
            now()->addHours(self::EXPORT_STATUS_TTL_HOURS)
        );
    }

    public function processQueuedExport(
        RecruitmentRequest $recruitmentRequest,
        array $selectedMatchIds,
        OdooService $odoo,
        CvStorageOptimizationService $storageOptimization
    ): array {
        $recruitmentRequest->loadMissing('jobOffer');

        $matches = CvMatch::with('cv')
            ->where('recruitment_request_id', $recruitmentRequest->id)
            ->whereIn('id', $selectedMatchIds)
            ->get();

        if ($matches->isEmpty()) {
            return [
                'status' => 'skipped',
                'message' => 'Aucun CV valide trouve.',
                'exported' => 0,
                'failed' => 0,
            ];
        }

        try {
            $clientName = trim($recruitmentRequest->client_name ?: 'Client Laravel RHS');
            $client = $odoo->findOrCreateClient($clientName);
            $department = $odoo->findOrCreateDepartment($clientName);

            Log::info('Odoo client resolved', [
                'request_id' => $recruitmentRequest->id,
                'client_name' => $clientName,
                'odoo_client_id' => $client['id'],
                'created' => $client['created'],
            ]);

            Log::info('Odoo department resolved', [
                'request_id' => $recruitmentRequest->id,
                'department_name' => $clientName,
                'odoo_department_id' => $department['id'],
                'created' => $department['created'],
            ]);

            $assignedOffer = $recruitmentRequest->jobOffer;
            $jobName = $recruitmentRequest->position_title
                ?: $assignedOffer?->title
                ?: $recruitmentRequest->reference
                ?: 'Demande Laravel RHS #' . $recruitmentRequest->id;

            $odooOfferId = null;

            if ($assignedOffer) {
                $odooOffer = $odoo->findOrCreateSmartOffer(
                    $assignedOffer->title ?: $jobName,
                    $this->buildOdooOfferPayload($recruitmentRequest)
                );
                $odooOfferId = (int) $odooOffer['id'];
            }

            $odooJobPayload = $this->buildOdooJobPayload($recruitmentRequest, (int) $client['id'], $odooOfferId);

            $demande = $odoo->findOrCreateDemande($jobName, (int) $department['id'], $odooJobPayload);
            $odooJobId = (int) $demande['id'];

            if ($odooOfferId) {
                $odoo->updateSmartOffer($odooOfferId, ['job_id' => $odooJobId]);
            }

            $stageId = $odoo->resolvePreselectionStageId();

            Log::info('Odoo recruitment demande resolved', [
                'request_id' => $recruitmentRequest->id,
                'job_name' => $jobName,
                'odoo_job_id' => $odooJobId,
                'created' => $demande['created'],
            ]);

            $exported = 0;
            $failed = 0;
            $smartSynced = 0;
            $smartFailed = 0;

            foreach ($matches as $match) {
                try {
                    $cv = $match->cv;

                    if (!$cv) {
                        $failed++;
                        Log::warning('Odoo CV export skipped: missing CV relation', [
                            'match_id' => $match->id,
                        ]);
                        continue;
                    }

                    $candidateName = $this->resolveCandidateName($cv);

                    $candidateId = (int) $odoo->createCandidate([
                        'partner_name' => $candidateName,
                        'email_from' => $cv->email,
                        'partner_phone' => $cv->phone,
                    ]);

                    Log::info('Odoo candidate created', [
                        'cv_id' => $cv->id,
                        'candidate_name' => $candidateName,
                        'odoo_candidate_id' => $candidateId,
                    ]);

                    $applicantId = (int) $odoo->createApplicant([
                        'candidate_id' => $candidateId,
                        'job_id' => $odooJobId,
                        'stage_id' => $stageId,
                        'applicant_notes' =>
                            "Importe depuis Laravel RHS\n" .
                            "Score matching: " . ($match->score ?? '-') . "%\n" .
                            "Resume: " . ($match->summary ?: '-'),
                    ]);

                    Log::info('Odoo applicant created', [
                        'cv_id' => $cv->id,
                        'match_id' => $match->id,
                        'odoo_applicant_id' => $applicantId,
                        'stage_id' => $stageId,
                    ]);

                    $binary = $this->readCvBinary($cv, $storageOptimization);

                    if ($binary !== null) {
                        $attachmentId = $odoo->uploadAttachment([
                            'name' => $cv->original_filename ?: ('cv-' . $cv->id . '.pdf'),
                            'datas' => base64_encode($binary),
                            'res_model' => config('odoo.applicant_model', 'hr.applicant'),
                            'res_id' => $applicantId,
                            'mimetype' => $cv->mime_type ?: 'application/pdf',
                        ]);

                        Log::info('Odoo CV attachment uploaded', [
                            'cv_id' => $cv->id,
                            'odoo_applicant_id' => $applicantId,
                            'odoo_attachment_id' => $attachmentId,
                        ]);
                    } else {
                        Log::warning('Odoo CV export without attachment: binary not found', [
                            'cv_id' => $cv->id,
                            'match_id' => $match->id,
                        ]);
                    }

                    try {
                        $smartResult = $odoo->pushSmartCandidateFromRtp(
                            $this->buildSmartCandidatePayload(
                                $cv,
                                $match,
                                $recruitmentRequest,
                                $binary !== null ? base64_encode($binary) : null,
                                $applicantId
                            )
                        );

                        if (!empty($smartResult['id'])) {
                            $odoo->updateSmartCandidate((int) $smartResult['id'], [
                                'job_id' => $odooOfferId,
                                'applicant_id' => $applicantId,
                            ]);
                        }

                        $smartSynced++;

                        Log::info('Odoo smart candidate synced', [
                            'cv_id' => $cv->id,
                            'match_id' => $match->id,
                            'smart_candidate_id' => $smartResult['id'] ?? null,
                            'smart_status' => $smartResult['status'] ?? null,
                            'odoo_offer_id' => $odooOfferId,
                            'odoo_applicant_id' => $applicantId,
                        ]);
                    } catch (\Throwable $e) {
                        $smartFailed++;

                        Log::warning('Odoo smart candidate sync failed', [
                            'cv_id' => $cv->id,
                            'match_id' => $match->id,
                            'message' => $e->getMessage(),
                        ]);
                    }

                    $exported++;
                } catch (\Throwable $e) {
                    $failed++;

                    Log::error('Odoo CV export item failed', [
                        'match_id' => $match->id ?? null,
                        'cv_id' => $match->cv_id ?? null,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            if ($exported > 0) {
                return [
                    'status' => 'success',
                    'message' => "{$exported} CV envoye(s) vers Odoo. {$failed} echec(s). Smart CV: {$smartSynced} synchronise(s), {$smartFailed} echec(s).",
                    'exported' => $exported,
                    'failed' => $failed,
                    'smart_synced' => $smartSynced,
                    'smart_failed' => $smartFailed,
                    'odoo_job_id' => $odooJobId,
                    'odoo_url' => $odoo->recruitmentUrl($odooJobId),
                ];
            }

            return [
                'status' => 'failed',
                'message' => "Aucun CV n a pu etre exporte vers Odoo. {$failed} echec(s). Demande Odoo ID: {$odooJobId}",
                'exported' => 0,
                'failed' => $failed,
                'odoo_job_id' => $odooJobId,
            ];
        } catch (\Throwable $e) {
            Log::error('Odoo selected CV export failed', [
                'request_id' => $recruitmentRequest->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'message' => 'Erreur export Odoo: ' . $e->getMessage(),
                'exported' => 0,
                'failed' => count($selectedMatchIds),
            ];
        }
    }

    private function readCvBinary($cv, CvStorageOptimizationService $storageOptimization): ?string
    {
        if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
            return file_get_contents(Storage::disk('local')->path($cv->encrypted_path));
        }

        if ($cv->source_type === 'application' && !empty($cv->source_id)) {
            $application = JobApplication::find($cv->source_id);

            if ($application && !empty($application->cv_path)) {
                $relativePath = ltrim($application->cv_path, '/');

                if (Storage::disk('public')->exists($relativePath)) {
                    return file_get_contents(Storage::disk('public')->path($relativePath));
                }
            }
        }

        return $storageOptimization->readBinary($cv);
    }

    private function buildSmartCandidatePayload(
        $cv,
        CvMatch $match,
        RecruitmentRequest $recruitmentRequest,
        ?string $cvBase64,
        ?int $odooApplicantId = null
    ): array {
        $offer = $recruitmentRequest->jobOffer;
        $candidateName = $this->resolveCandidateName($cv);
        [$firstName, $lastName] = $this->splitCandidateName($candidateName);
        $profile = is_array($cv->structured_profile) ? $cv->structured_profile : [];
        $skills = $this->extractProfileList($profile, ['skills', 'competences', 'competencies', 'technical_skills']);
        $languages = $this->extractLanguagePayload($profile, $recruitmentRequest);
        $missionRef = $offer?->id ?: $recruitmentRequest->reference;
        $rawText = trim((string) ($cv->encrypted_extracted_text ?: $match->summary ?: ''));

        if (empty($skills)) {
            $skills = $this->splitListText($offer?->requirements ?: $recruitmentRequest->specific_knowledge);
        }

        $educationValue = $profile['education_level'] ?? $profile['education'] ?? $recruitmentRequest->education;
        $experienceValue = $profile['experience_years'] ?? $profile['total_experience_years'] ?? $profile['experience'] ?? $recruitmentRequest->experience_years;

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $cv->email ?: '',
            'phone' => $cv->phone ?: '',
            'city' => $cv->city ?: $recruitmentRequest->work_location ?: $offer?->location,
            'region' => $recruitmentRequest->work_location ?: $offer?->location,
            'education_level' => $this->normalizeEducationLevel($educationValue),
            'total_experience_years' => $this->parseExperienceYears($experienceValue),
            'skills' => $skills,
            'languages' => $languages,
            'current_title' => $cv->current_title ?: $recruitmentRequest->position_title ?: $offer?->title,
            'sector' => $offer?->sector,
            'salary_expectation' => $recruitmentRequest->monthly_salary,
            'availability' => $this->mapCandidateAvailability($recruitmentRequest->availability),
            'raw_text' => $rawText,
            'source' => $this->mapCandidateSource($cv->source_type),
            'mission_ref' => $missionRef,
            'offre_id' => $missionRef,
            'parsed_data' => [
                'rhs_cv_id' => $cv->id,
                'rhs_match_id' => $match->id,
                'rhs_recruitment_request_id' => $recruitmentRequest->id,
                'rhs_job_offer_id' => $offer?->id,
                'odoo_applicant_id' => $odooApplicantId,
                'matching_score' => $match->score,
                'matching_summary' => $match->summary,
                'score_breakdown' => $match->score_breakdown,
            ],
            'rtp_candidate_id' => 'rhs-cv-' . $cv->id . '-request-' . $recruitmentRequest->id,
            'cv_base64' => $cvBase64,
            'cv_filename' => $cv->original_filename ?: ('cv-' . $cv->id . '.pdf'),
        ];
    }

    private function splitCandidateName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? $name,
            $parts[1] ?? '',
        ];
    }

    private function resolveCandidateName($cv): string
    {
        $profile = is_array($cv->structured_profile) ? $cv->structured_profile : [];

        foreach ([
            $cv->candidate_name ?? null,
            $profile['full_name'] ?? null,
            $profile['name'] ?? null,
            $profile['candidate_name'] ?? null,
        ] as $candidate) {
            $candidate = $this->cleanCandidateNameCandidate((string) $candidate);

            if ($candidate !== '') {
                return $candidate;
            }
        }

        $emailName = $this->candidateNameFromEmail((string) ($cv->email ?? ''));

        if ($emailName !== '') {
            return $emailName;
        }

        return 'Candidat RHS #' . (int) $cv->id;
    }

    private function cleanCandidateNameCandidate(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));
        $value = preg_replace('/\.(pdf|docx?|txt)$/i', '', $value);
        $value = trim(str_replace(['_', '.'], ' ', (string) $value));

        if ($value === '') {
            return '';
        }

        $normalized = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value);
        $normalized = trim(preg_replace('/[^a-z0-9]+/', ' ', $normalized));

        if (
            $normalized === ''
            || preg_match('/^[a-f0-9]{14,}$/i', str_replace(' ', '', $value))
            || preg_match('/\b(cv|resume|profil|profile|scan|copie|copy|final|version|pdf|doc|docx)\b/i', $normalized)
            || preg_match('/\b(poste|fonction|competence|formation|experience|telephone|email)\b/i', $normalized)
        ) {
            return '';
        }

        $tokens = preg_split('/\s+/', $value) ?: [];
        $tokens = array_values(array_filter($tokens, fn ($token) => preg_match('/^[\pL\'-]{2,}$/u', $token)));

        if (count($tokens) < 2 || count($tokens) > 5) {
            return '';
        }

        return mb_convert_case(implode(' ', $tokens), MB_CASE_TITLE, 'UTF-8');
    }

    private function candidateNameFromEmail(string $email): string
    {
        if (!preg_match('/^([A-Z0-9._%+\-]+)/i', $email, $match)) {
            return '';
        }

        $local = preg_replace('/[0-9]+/', ' ', $match[1]);
        $local = trim(str_replace(['.', '_', '-'], ' ', (string) $local));
        $parts = array_values(array_filter(preg_split('/\s+/', $local) ?: [], fn ($part) => mb_strlen($part) >= 2));

        return count($parts) >= 2
            ? mb_convert_case(implode(' ', array_slice($parts, 0, 4)), MB_CASE_TITLE, 'UTF-8')
            : '';
    }

    private function extractProfileList(array $profile, array $keys): array
    {
        foreach ($keys as $key) {
            $value = $profile[$key] ?? null;

            if (is_array($value)) {
                return collect($value)
                    ->map(fn ($item) => is_array($item) ? ($item['name'] ?? $item['label'] ?? null) : $item)
                    ->filter()
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            if (is_string($value) && trim($value) !== '') {
                return $this->splitListText($value);
            }
        }

        return [];
    }

    private function extractLanguagePayload(array $profile, RecruitmentRequest $recruitmentRequest): array
    {
        $languages = $profile['languages'] ?? $profile['langues'] ?? null;

        if (is_array($languages)) {
            return collect($languages)
                ->map(function ($language) {
                    if (is_array($language)) {
                        return [
                            'lang' => $language['lang'] ?? $language['language'] ?? $language['name'] ?? '',
                            'level' => $language['level'] ?? $language['niveau'] ?? '',
                        ];
                    }

                    return ['lang' => (string) $language, 'level' => ''];
                })
                ->filter(fn ($language) => trim((string) $language['lang']) !== '')
                ->values()
                ->all();
        }

        $requestLanguages = collect([
            'Arabe' => $recruitmentRequest->lang_ar,
            'Francais' => $recruitmentRequest->lang_fr,
            'Anglais' => $recruitmentRequest->lang_en,
            'Espagnol' => $recruitmentRequest->lang_es,
        ])->filter()->keys();

        if ($recruitmentRequest->other_language) {
            $requestLanguages->push($recruitmentRequest->other_language);
        }

        return $requestLanguages
            ->map(fn ($language) => ['lang' => $language, 'level' => ''])
            ->values()
            ->all();
    }

    private function splitListText(?string $value): array
    {
        return collect(preg_split('/[,;\n\r]+/', (string) $value) ?: [])
            ->map(fn ($item) => trim(strip_tags((string) $item)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeEducationLevel($value): string
    {
        if (is_array($value)) {
            $value = $value['level'] ?? $value['niveau'] ?? $value['degree'] ?? $value['name'] ?? implode(' ', array_filter($value, 'is_scalar'));
        }

        $value = $this->normalizeOdooSelectionText((string) $value);

        return match (true) {
            str_contains($value, 'doctor') || str_contains($value, 'phd') => 'doctorat',
            str_contains($value, 'bac+5') || str_contains($value, 'master') || str_contains($value, 'ingenieur') => 'bac5',
            str_contains($value, 'bac+4') => 'bac5',
            str_contains($value, 'bac+3') || str_contains($value, 'licence') || str_contains($value, 'bachelor') => 'bac3',
            str_contains($value, 'bac+2') || str_contains($value, 'dut') || str_contains($value, 'bts') || str_contains($value, 'deug') => 'bac2',
            str_contains($value, 'bac') => 'bac',
            default => 'other',
        };
    }

    private function parseExperienceYears($value): float
    {
        if (is_array($value)) {
            $value = $value['years'] ?? $value['annees'] ?? $value['total_years'] ?? implode(' ', array_filter($value, 'is_scalar'));
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return preg_match('/\d+(?:[.,]\d+)?/', (string) $value, $match)
            ? (float) str_replace(',', '.', $match[0])
            : 0.0;
    }

    private function mapCandidateAvailability(?string $value): ?string
    {
        $value = $this->normalizeOdooSelectionText($value);

        return match (true) {
            str_contains($value, 'immed') || str_contains($value, 'asap') => 'immediat',
            str_contains($value, '< 1') || str_contains($value, 'moins') || str_contains($value, '1 mois') => 'moins_1_mois',
            str_contains($value, '1-3') || str_contains($value, '1 a 3') || str_contains($value, '3 mois') => '1_3_mois',
            str_contains($value, 'plus') || str_contains($value, '> 3') => 'plus_3_mois',
            default => null,
        };
    }

    private function mapCandidateSource(?string $sourceType): string
    {
        return match ($sourceType) {
            'application' => 'website',
            'manual', 'external_db' => 'import_masse',
            default => 'other',
        };
    }

    private function buildOdooJobPayload(RecruitmentRequest $recruitmentRequest, int $clientId, ?int $odooOfferId = null): array
    {
        $offer = $recruitmentRequest->jobOffer;
        $description = $this->buildOdooDescription($recruitmentRequest);
        $requirements = trim(implode("\n\n", array_filter([
            $recruitmentRequest->specific_knowledge ? "Connaissances specifiques:\n" . $recruitmentRequest->specific_knowledge : null,
            $recruitmentRequest->personal_qualities ? "Qualites personnelles:\n" . $recruitmentRequest->personal_qualities : null,
            $offer?->requirements ? "Exigences de l offre:\n" . $offer->requirements : null,
        ])));

        return array_merge([
            'description' => $description,
            'website_description' => $description,
            'requirements' => $requirements,
            'no_of_recruitment' => $recruitmentRequest->candidate_count,
            'address_id' => $clientId,
            'offre_ia_id' => $odooOfferId,
        ], $this->buildOdooDirectRtpFieldValues($recruitmentRequest));
    }

    private function buildOdooRtpFieldValues(RecruitmentRequest $recruitmentRequest): array
    {
        $offer = $recruitmentRequest->jobOffer;
        $languages = collect([
            'Arabe' => $recruitmentRequest->lang_ar,
            'Francais' => $recruitmentRequest->lang_fr,
            'Anglais' => $recruitmentRequest->lang_en,
            'Espagnol' => $recruitmentRequest->lang_es,
        ])->filter()->keys()->push($recruitmentRequest->other_language)->filter()->implode(', ');

        $budget = trim(implode(' / ', array_filter([
            $recruitmentRequest->budget_type,
            $recruitmentRequest->monthly_salary,
        ])));

        return [
            'ID Offre RTP' => $offer?->id,
            'Affaire N°' => $recruitmentRequest->reference,
            'Client / Demandeur' => $recruitmentRequest->client_name,
            'Motif de recrutement' => $recruitmentRequest->recruitment_reason,
            'Budget du poste' => $budget,
            'Date prévue de démarrage' => optional($recruitmentRequest->planned_start_date)->format('Y-m-d'),
            'Âge souhaité' => $recruitmentRequest->age,
            'Sexe souhaité' => $recruitmentRequest->gender,
            'Missions et tâches' => $recruitmentRequest->missions,
            'Qualités personnelles' => $recruitmentRequest->personal_qualities,
            'Connaissances spécifiques' => $recruitmentRequest->specific_knowledge,
            'Autres avantages' => $recruitmentRequest->other_benefits,
            'Langues' => $languages,
            'Formation' => $recruitmentRequest->education,
            'Expérience' => $recruitmentRequest->experience_years,
            'Disponibilité' => $recruitmentRequest->availability,
            'Contrat' => $recruitmentRequest->contract_type,
            'Lieu' => $recruitmentRequest->work_location,
            'Offre assignée' => $offer?->title,
            'Titre offre' => $offer?->title,
            'Entreprise' => $offer?->company,
            'Lieu offre' => $offer?->location,
            'Contrat offre' => $offer?->contract_type,
            'Secteur' => $offer?->sector,
            'URL publique' => $offer?->slug ? route('jobs.show', $offer->slug) : null,
            'Description offre' => $offer?->description,
            'Missions offre' => $offer?->missions,
            'Exigences offre' => $offer?->requirements,
        ];
    }

    private function buildOdooDirectRtpFieldValues(RecruitmentRequest $recruitmentRequest): array
    {
        $offer = $recruitmentRequest->jobOffer;
        $budget = trim(implode(' / ', array_filter([
            $recruitmentRequest->budget_type,
            $recruitmentRequest->monthly_salary,
        ])));

        return [
            'reference_externe' => $offer?->id ?: $recruitmentRequest->reference,
            'affaire_numero' => $recruitmentRequest->reference,
            'client_demandeur' => $recruitmentRequest->client_name,
            'motif_recrutement' => $this->odooMotifValue($recruitmentRequest->recruitment_reason),
            'budget_poste' => $budget,
            'date_prevue_demarrage' => optional($recruitmentRequest->planned_start_date)->format('Y-m-d'),
            'age_souhaite' => $recruitmentRequest->age,
            'sexe_souhaite' => $this->odooGenderValue($recruitmentRequest->gender),
            'missions_taches' => $recruitmentRequest->missions,
            'qualites_personnelles' => $recruitmentRequest->personal_qualities,
            'connaissances_specifiques' => $recruitmentRequest->specific_knowledge,
            'autres_avantages' => $recruitmentRequest->other_benefits,
        ];
    }

    private function buildOdooOfferPayload(RecruitmentRequest $recruitmentRequest): array
    {
        $offer = $recruitmentRequest->jobOffer;
        $budget = trim(implode(' / ', array_filter([
            $recruitmentRequest->budget_type,
            $recruitmentRequest->monthly_salary,
        ])));

        return [
            'name' => $offer?->title ?: $recruitmentRequest->position_title ?: 'Offre Laravel RHS #' . $recruitmentRequest->id,
            'description' => $offer?->description ?: $recruitmentRequest->missions,
            'profil_recherche' => $offer?->excerpt ?: $recruitmentRequest->personal_qualities,
            'competences_requises' => $offer?->requirements ?: $recruitmentRequest->specific_knowledge,
            'reference_externe' => $offer?->id ?: $recruitmentRequest->reference,
            'nb_shortlist' => $recruitmentRequest->candidate_count,
            'experience_min' => $this->parseOdooInteger($recruitmentRequest->experience_years),
            'localisation' => $offer?->location ?: $recruitmentRequest->work_location,
            'type_contrat' => $this->odooContractValue($offer?->contract_type ?: $recruitmentRequest->contract_type),
            'state' => 'open',
            'secteur_activite' => $offer?->sector,
            'affaire_numero' => $recruitmentRequest->reference,
            'client_demandeur' => $recruitmentRequest->client_name,
            'motif_recrutement' => $this->odooMotifValue($recruitmentRequest->recruitment_reason),
            'budget_poste' => $budget,
            'date_prevue_demarrage' => optional($recruitmentRequest->planned_start_date)->format('Y-m-d'),
            'age_souhaite' => $recruitmentRequest->age,
            'sexe_souhaite' => $this->odooGenderValue($recruitmentRequest->gender),
            'missions_taches' => $offer?->missions ?: $recruitmentRequest->missions,
            'qualites_personnelles' => $recruitmentRequest->personal_qualities,
            'connaissances_specifiques' => $recruitmentRequest->specific_knowledge,
            'autres_avantages' => $recruitmentRequest->other_benefits,
        ];
    }

    private function odooGenderValue(?string $value): ?string
    {
        $value = $this->normalizeOdooSelectionText($value);

        return match (true) {
            str_contains($value, 'homme') || str_contains($value, 'male') => 'homme',
            str_contains($value, 'femme') || str_contains($value, 'female') => 'femme',
            $value !== '' => 'indifferent',
            default => null,
        };
    }

    private function odooMotifValue(?string $value): ?string
    {
        $value = $this->normalizeOdooSelectionText($value);

        return match (true) {
            str_contains($value, 'remplacement') => 'remplacement',
            str_contains($value, 'creation') || str_contains($value, 'nouveau') => 'creation_poste',
            str_contains($value, 'surcroit') || str_contains($value, 'accroissement') || str_contains($value, 'activite') => 'surcroit_activite',
            str_contains($value, 'projet') || str_contains($value, 'temporaire') || str_contains($value, 'saisonnier') => 'projet',
            $value !== '' => 'autre',
            default => null,
        };
    }

    private function odooContractValue(?string $value): ?string
    {
        $value = $this->normalizeOdooSelectionText($value);

        return match (true) {
            str_contains($value, 'cdi') => 'cdi',
            str_contains($value, 'cdd') => 'cdd',
            str_contains($value, 'stage') => 'stage',
            str_contains($value, 'alternance') => 'alternance',
            str_contains($value, 'freelance') => 'freelance',
            default => null,
        };
    }

    private function normalizeOdooSelectionText(?string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value) ?: (string) $value;

        return strtolower($value);
    }

    private function parseOdooInteger(?string $value): ?int
    {
        return preg_match('/\d+/', (string) $value, $match) ? (int) $match[0] : null;
    }

    private function buildOdooDescription(RecruitmentRequest $recruitmentRequest): string
    {
        $offer = $recruitmentRequest->jobOffer;
        $languages = collect([
            'Arabe' => $recruitmentRequest->lang_ar,
            'Francais' => $recruitmentRequest->lang_fr,
            'Anglais' => $recruitmentRequest->lang_en,
            'Espagnol' => $recruitmentRequest->lang_es,
        ])->filter()->keys()->push($recruitmentRequest->other_language)->filter()->implode(', ');

        $lines = [
            'Importe depuis Laravel RHS',
            '',
            'Client: ' . ($recruitmentRequest->client_name ?: '-'),
            'Reference: ' . ($recruitmentRequest->reference ?: '-'),
            'Date demande Laravel: ' . (optional($recruitmentRequest->request_date)->format('Y-m-d') ?: '-'),
            'Poste demande: ' . ($recruitmentRequest->position_title ?: '-'),
            'Nombre de candidats: ' . ($recruitmentRequest->candidate_count ?: '-'),
            'Langues: ' . ($languages ?: '-'),
        ];

        if ($offer) {
            $lines = array_merge($lines, [
                '',
                'Offre assignee:',
                'Titre: ' . ($offer->title ?: '-'),
                'Entreprise: ' . ($offer->company ?: '-'),
                'Lieu offre: ' . ($offer->location ?: '-'),
                'Contrat offre: ' . ($offer->contract_type ?: '-'),
                'Secteur: ' . ($offer->sector ?: '-'),
                'URL publique: ' . ($offer->slug ? route('jobs.show', $offer->slug) : '-'),
                'Extrait:',
                $offer->excerpt ?: '-',
                '',
                'Description offre:',
                $offer->description ?: '-',
                '',
                'Missions offre:',
                $offer->missions ?: '-',
                '',
                'Exigences offre:',
                $offer->requirements ?: '-',
            ]);
        }

        return implode("\n", $lines);
    }
}
