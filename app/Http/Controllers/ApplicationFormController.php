<?php

namespace App\Http\Controllers;

use App\Jobs\SyncApplicationCvToBankJob;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Mail\ApplicationReceivedMail;
use App\Models\JobApplication;
use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
    public function store(StoreJobApplicationRequest $request)
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
                SyncApplicationCvToBankJob::dispatch($application->id)->afterCommit();
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
}
