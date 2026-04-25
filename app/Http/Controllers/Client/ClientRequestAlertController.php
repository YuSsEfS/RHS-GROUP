<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;

class ClientRequestAlertController extends Controller
{
    public function store(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        abort_unless($recruitmentRequest->client_user_id === auth()->id(), 403);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        ClientRequestAlert::create([
            'recruitment_request_id' => $recruitmentRequest->id,
            'client_user_id' => auth()->id(),
            'message' => $validated['message'] ?? null,
            'status' => ClientRequestAlert::STATUS_NEW,
        ]);

        return redirect()
            ->route('client.dashboard')
            ->with('success', 'Votre relance a ete envoyee a l equipe RHS.');
    }
}
