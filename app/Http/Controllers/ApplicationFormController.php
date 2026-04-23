<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

use App\Http\Requests\StoreJobApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Mail\ApplicationReceivedMail;

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

        // If spontaneous, ignore offer
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
            $data['type'] = 'spontaneous'; // ✅ set type
        } else {
            $data['type'] = 'offer'; // ✅ set type

            $offer = JobOffer::find($data['job_offer_id']);
            $data['position'] = $offer ? $offer->title : 'Candidature';
        }

        // Uploads
        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('applications/cv', 'public');
        }

        if ($request->hasFile('letter')) {
            $data['letter_path'] = $request->file('letter')->store('applications/letters', 'public');
        }

        $application = JobApplication::create($data);

        // ✅ THIS is where we load the related offer
        $application->load('offer');

        try {
            Mail::to($application->email)->send(new ApplicationReceivedMail($application));
        } catch (\Throwable $e) {
            \Log::error('Candidate confirmation mail failed: ' . $e->getMessage());
        }

        return back()->with(
            'success',
            'Candidature envoyée avec succès. Un email de confirmation vous a été envoyé.'
        );
    }
}
