<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientRecruitmentRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', 'all'));
        $q = trim((string) $request->query('q', ''));

        $query = RecruitmentRequest::query()
            ->with('clientUser')
            ->withCount('matches')
            ->whereNotNull('client_user_id')
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('request_status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('client_name', 'like', "%{$q}%")
                        ->orWhere('reference', 'like', "%{$q}%")
                        ->orWhere('position_title', 'like', "%{$q}%");
                });
            });

        $requests = (clone $query)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        RecruitmentRequest::query()
            ->whereNotNull('client_user_id')
            ->whereNull('admin_seen_at')
            ->update(['admin_seen_at' => now()]);

        return view('admin.client_recruitment_requests.index', [
            'requests' => $requests,
            'statuses' => RecruitmentRequest::availableStatuses(),
            'status' => $status,
            'q' => $q,
        ]);
    }

    public function edit(RecruitmentRequest $clientRecruitmentRequest)
    {
        abort_unless($clientRecruitmentRequest->client_user_id, 404);

        if (is_null($clientRecruitmentRequest->admin_seen_at)) {
            $clientRecruitmentRequest->forceFill([
                'admin_seen_at' => now(),
            ])->save();
        }

        return view('admin.client_recruitment_requests.edit', [
            'recruitmentRequest' => $clientRecruitmentRequest->loadCount('matches'),
            'statuses' => RecruitmentRequest::availableStatuses(),
        ]);
    }

    public function update(Request $request, RecruitmentRequest $clientRecruitmentRequest)
    {
        abort_unless($clientRecruitmentRequest->client_user_id, 404);

        $validated = $request->validate([
            'request_status' => ['required', Rule::in(array_keys(RecruitmentRequest::availableStatuses()))],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $clientRecruitmentRequest->update($validated);

        return redirect()
            ->route('admin.client-recruitment-requests.index')
            ->with('success', 'Demande client mise à jour avec succès.');
    }
}
