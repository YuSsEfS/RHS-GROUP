<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Offer;

class ApplyController extends Controller
{
    public function create(Request $request)
    {
        $offer = null;

        // read ?offer=ID from URL
        if ($request->filled('offer')) {
            $offer = Offer::find($request->offer);
        }

        return view('pages.apply', compact('offer'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255'],
            'phone'     => ['required', 'string', 'max:50'],
            'position'  => ['nullable', 'string', 'max:255'],
            'message'   => ['nullable', 'string'],
            'offer_id'  => ['nullable', 'integer'],
            'cv'        => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'], // 5MB
        ]);

        // store the CV
        $cvPath = $request->file('cv')->store('cvs', 'public');

        // ✅ If you already have an Application model/table, save here.
        // Otherwise, keep it simple: redirect with success message.
        // Example (optional):
        // Application::create([...]);

        return redirect()
            ->route('apply', ['offer' => $request->offer_id])
            ->with('success', '✅ Candidature envoyée avec succès. Nous vous contacterons bientôt.');
    }
}
