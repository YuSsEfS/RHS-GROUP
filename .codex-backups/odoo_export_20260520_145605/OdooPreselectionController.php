<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CvMatch;
use App\Models\JobApplication;
use App\Models\RecruitmentRequest;
use App\Services\CvStorageOptimizationService;
use App\Services\OdooService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OdooPreselectionController extends Controller
{
    public function exportSelected(
        Request $request,
        RecruitmentRequest $recruitmentRequest,
        OdooService $odoo,
        CvStorageOptimizationService $storageOptimization
    ) {
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

            $jobName = $recruitmentRequest->position_title
                ?: $recruitmentRequest->reference
                ?: 'Demande Laravel RHS #' . $recruitmentRequest->id;

            $description =
                "Importe depuis Laravel RHS\n\n" .
                "Client Odoo ID: {$client['id']}\n" .
                "Departement Odoo ID: {$department['id']}\n" .
                "Client: " . ($recruitmentRequest->client_name ?: '-') . "\n" .
                "Reference: " . ($recruitmentRequest->reference ?: '-') . "\n" .
                "Date demande Laravel: " . optional($recruitmentRequest->request_date)->format('Y-m-d') . "\n" .
                "Lieu: " . ($recruitmentRequest->work_location ?: '-') . "\n" .
                "Contrat: " . ($recruitmentRequest->contract_type ?: '-') . "\n" .
                "Experience: " . ($recruitmentRequest->experience_years ?: '-') . "\n\n" .
                "Missions:\n" . ($recruitmentRequest->missions ?: '-') . "\n\n" .
                "Qualites personnelles:\n" . ($recruitmentRequest->personal_qualities ?: '-') . "\n\n" .
                "Connaissances specifiques:\n" . ($recruitmentRequest->specific_knowledge ?: '-');

            $demande = $odoo->findOrCreateDemande($jobName, (int) $department['id'], [
                'description' => $description,
            ]);
            $odooJobId = (int) $demande['id'];
            $stageId = $odoo->resolvePreselectionStageId();

            Log::info('Odoo recruitment demande resolved', [
                'request_id' => $recruitmentRequest->id,
                'job_name' => $jobName,
                'odoo_job_id' => $odooJobId,
                'created' => $demande['created'],
            ]);

            $exported = 0;
            $failed = 0;

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

                    $candidateName = $cv->candidate_name
                        ?: pathinfo($cv->original_filename ?: 'Candidat', PATHINFO_FILENAME);

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
                return redirect()
                    ->away($odoo->recruitmentUrl($odooJobId))
                    ->with('success', "{$exported} CV envoye(s) vers Odoo. {$failed} echec(s).");
            }

            return back()->with(
                'error',
                "Aucun CV n a pu etre exporte vers Odoo. {$failed} echec(s). Demande Odoo ID: {$odooJobId}"
            );
        } catch (\Throwable $e) {
            Log::error('Odoo selected CV export failed', [
                'request_id' => $recruitmentRequest->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur export Odoo: ' . $e->getMessage());
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
}
