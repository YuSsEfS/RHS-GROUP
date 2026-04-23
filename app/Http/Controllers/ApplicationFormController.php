<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobApplicationRequest;
use App\Mail\ApplicationReceivedMail;
use App\Models\Cv;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Services\OpenAiRecruitmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class ApplicationFormController extends Controller
{
    /**
     * Show apply form (GET /postuler)
     * Accepts:
     * - /postuler?offer=ID
     * - /postuler?type=spontaneous
     */
    public function create(Request $request)
    {
        $offer = null;
        $isSpontaneous = $request->get('type') === 'spontaneous';

        if (!$isSpontaneous && $request->filled('offer')) {
            $offer = JobOffer::find($request->offer);
        }

        return view('pages.apply', compact('offer', 'isSpontaneous'));
    }

    /**
     * Submit apply form (POST /postuler)
     */
    public function store(StoreJobApplicationRequest $request, OpenAiRecruitmentService $ai)
    {
        $data = $request->validated();

        $isSpontaneous =
            ($request->input('type') === 'spontaneous')
            || empty($data['job_offer_id'] ?? null);

        if ($isSpontaneous) {
            $data['job_offer_id'] = null;
            $data['position'] = 'Candidature spontanée';
            $data['type'] = 'spontaneous';
        } else {
            $data['type'] = 'offer';

            $offer = JobOffer::find($data['job_offer_id']);
            $data['position'] = $offer ? $offer->title : 'Candidature';
        }

        /*
        |--------------------------------------------------------------------------
        | Upload application files first
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('applications/cv', 'public');
        }

        if ($request->hasFile('letter')) {
            $data['letter_path'] = $request->file('letter')->store('applications/letters', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Create application
        |--------------------------------------------------------------------------
        */
        $application = JobApplication::create($data);
        $application->load('offer');

        /*
        |--------------------------------------------------------------------------
        | Sync CV into CV bank automatically
        |--------------------------------------------------------------------------
        */
        try {
            if ($request->hasFile('cv')) {
                $this->syncApplicationCvToBank($application, $request->file('cv'), $ai);
            }
        } catch (\Throwable $e) {
            Log::error('Application CV bank sync failed: ' . $e->getMessage(), [
                'application_id' => $application->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Confirmation email
        |--------------------------------------------------------------------------
        */
        try {
            Mail::to($application->email)->send(new ApplicationReceivedMail($application));
        } catch (\Throwable $e) {
            Log::error('Candidate confirmation mail failed: ' . $e->getMessage(), [
                'application_id' => $application->id,
            ]);
        }

        return back()->with(
            'success',
            'Candidature envoyée avec succès. Un email de confirmation vous a été envoyé.'
        );
    }

    /**
     * Copy the application CV into the CV bank (cvs table + local private storage)
     */
    private function syncApplicationCvToBank(JobApplication $application, $uploadedFile, OpenAiRecruitmentService $ai): void
    {
        $binary = file_get_contents($uploadedFile->getRealPath());
        $hash = hash('sha256', $binary);
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        /*
        |--------------------------------------------------------------------------
        | If this application already has a CV in bank, update it
        |--------------------------------------------------------------------------
        */
        $existingBySource = null;

        if (
            Schema::hasColumn('cvs', 'source_type') &&
            Schema::hasColumn('cvs', 'source_id')
        ) {
            $existingBySource = Cv::query()
                ->where('source_type', 'application')
                ->where('source_id', $application->id)
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | If same file already exists, reuse/update it instead of duplicating blindly
        |--------------------------------------------------------------------------
        */
        $existingByHash = Cv::query()
            ->where('file_hash', $hash)
            ->first();

        $text = $this->extractTextFromFile($uploadedFile->getRealPath(), $extension);

        $profile = [
            'full_name' => $application->full_name,
            'email' => $application->email,
            'phone' => $application->phone,
            'title' => $application->position,
            'years_experience' => null,
            'education' => null,
            'languages' => [],
            'technical_skills' => [],
            'soft_skills' => [],
            'industries' => [],
            'certifications' => [],
            'summary' => $text ? mb_substr($text, 0, 1500) : null,
            'city' => $application->city,
        ];

        try {
            if (!empty($text)) {
                $structured = $ai->structureCv($text);

                if (is_array($structured) && !empty($structured)) {
                    $profile = array_merge($profile, $structured);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AI CV structuring failed for application CV: ' . $e->getMessage(), [
                'application_id' => $application->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | If same application already synced before, replace/update it
        |--------------------------------------------------------------------------
        */
        if ($existingBySource) {
            if (!empty($existingBySource->encrypted_path) && Storage::disk('local')->exists($existingBySource->encrypted_path)) {
                Storage::disk('local')->delete($existingBySource->encrypted_path);
            }

            $storedPath = 'private/cvs/' . uniqid('cv_app_', true) . '.' . $extension;
            Storage::disk('local')->put($storedPath, $binary);

            $updateData = [
                'candidate_name' => $profile['full_name'] ?? $application->full_name,
                'email' => $profile['email'] ?? $application->email,
                'phone' => $profile['phone'] ?? $application->phone,
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'encrypted_path' => $storedPath,
                'encrypted_extracted_text' => $text,
                'structured_profile' => $profile,
                'file_hash' => $hash,
                'uploaded_at' => now(),
            ];

            if (Schema::hasColumn('cvs', 'source_type')) {
                $updateData['source_type'] = 'application';
            }

            if (Schema::hasColumn('cvs', 'source_id')) {
                $updateData['source_id'] = $application->id;
            }

            if (Schema::hasColumn('cvs', 'city')) {
                $updateData['city'] = $application->city;
            }

            if (Schema::hasColumn('cvs', 'current_title')) {
                $updateData['current_title'] = $application->position;
            }

            if (Schema::hasColumn('cvs', 'is_active')) {
                $updateData['is_active'] = true;
            }

            $existingBySource->update($updateData);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | If same file hash exists already, just link/update its source info
        |--------------------------------------------------------------------------
        */
        if ($existingByHash) {
            $updateData = [
                'candidate_name' => $existingByHash->candidate_name ?: ($profile['full_name'] ?? $application->full_name),
                'email' => $existingByHash->email ?: ($profile['email'] ?? $application->email),
                'phone' => $existingByHash->phone ?: ($profile['phone'] ?? $application->phone),
            ];

            if (Schema::hasColumn('cvs', 'source_type')) {
                $updateData['source_type'] = 'application';
            }

            if (Schema::hasColumn('cvs', 'source_id')) {
                $updateData['source_id'] = $application->id;
            }

            if (Schema::hasColumn('cvs', 'city')) {
                $updateData['city'] = $existingByHash->city ?: $application->city;
            }

            if (Schema::hasColumn('cvs', 'current_title')) {
                $updateData['current_title'] = $existingByHash->current_title ?: $application->position;
            }

            if (Schema::hasColumn('cvs', 'is_active')) {
                $updateData['is_active'] = true;
            }

            $existingByHash->update($updateData);
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Otherwise create a fresh CV bank record
        |--------------------------------------------------------------------------
        */
        $storedPath = 'private/cvs/' . uniqid('cv_app_', true) . '.' . $extension;
        Storage::disk('local')->put($storedPath, $binary);

        $createData = [
            'candidate_name' => $profile['full_name'] ?? $application->full_name,
            'email' => $profile['email'] ?? $application->email,
            'phone' => $profile['phone'] ?? $application->phone,
            'original_filename' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getMimeType(),
            'file_size' => $uploadedFile->getSize(),
            'encrypted_path' => $storedPath,
            'encrypted_extracted_text' => $text,
            'structured_profile' => $profile,
            'file_hash' => $hash,
            'uploaded_at' => now(),
        ];

        if (Schema::hasColumn('cvs', 'source_type')) {
            $createData['source_type'] = 'application';
        }

        if (Schema::hasColumn('cvs', 'source_id')) {
            $createData['source_id'] = $application->id;
        }

        if (Schema::hasColumn('cvs', 'city')) {
            $createData['city'] = $application->city;
        }

        if (Schema::hasColumn('cvs', 'current_title')) {
            $createData['current_title'] = $application->position;
        }

        if (Schema::hasColumn('cvs', 'is_active')) {
            $createData['is_active'] = true;
        }

        Cv::create($createData);
    }

    private function extractTextFromFile(string $filePath, string $extension): string
    {
        try {
            if ($extension === 'pdf') {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($filePath);
                return trim($pdf->getText());
            }

            if (in_array($extension, ['doc', 'docx'], true)) {
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
                return trim((string) file_get_contents($filePath));
            }
        } catch (\Throwable $e) {
            Log::warning('CV text extraction failed: ' . $e->getMessage(), [
                'file_path' => $filePath,
                'extension' => $extension,
            ]);
        }

        return '';
    }
}