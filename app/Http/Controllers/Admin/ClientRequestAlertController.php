<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientRequestAlertController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $client = (string) $request->query('client', 'all');
        $requestId = trim((string) $request->query('request', ''));

        $alerts = ClientRequestAlert::query()
            ->with(['clientUser', 'recruitmentRequest'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($client !== 'all', fn ($query) => $query->where('client_user_id', (int) $client))
            ->when($requestId !== '', fn ($query) => $query->where('recruitment_request_id', (int) $requestId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        ClientRequestAlert::query()
            ->whereNull('admin_seen_at')
            ->update(['admin_seen_at' => now()]);

        return view('admin.client_request_alerts.index', [
            'alerts' => $alerts,
            'statuses' => ClientRequestAlert::availableStatuses(),
            'clients' => User::query()->where('role', User::ROLE_CLIENT)->orderBy('name')->get(['id', 'name']),
            'status' => $status,
            'client' => $client,
            'requestId' => $requestId,
        ]);
    }

    public function edit(ClientRequestAlert $clientRequestAlert)
    {
        if (is_null($clientRequestAlert->admin_seen_at)) {
            $clientRequestAlert->update(['admin_seen_at' => now()]);
        }

        return view('admin.client_request_alerts.edit', [
            'alert' => $clientRequestAlert->load(['clientUser', 'recruitmentRequest', 'responder']),
            'statuses' => ClientRequestAlert::availableStatuses(),
            'quickResponses' => ClientRequestAlert::quickResponses(),
        ]);
    }

    public function update(Request $request, ClientRequestAlert $clientRequestAlert)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ClientRequestAlert::availableStatuses()))],
            'quick_response' => ['nullable', Rule::in(ClientRequestAlert::quickResponses())],
            'admin_response' => ['nullable', 'string', 'max:3000'],
        ]);

        $responseParts = array_filter([
            $validated['quick_response'] ?? null,
            $validated['admin_response'] ?? null,
        ]);

        $clientRequestAlert->update([
            'status' => $validated['status'],
            'admin_response' => empty($responseParts) ? null : implode("\n\n", $responseParts),
            'admin_seen_at' => $clientRequestAlert->admin_seen_at ?: now(),
            'responded_at' => now(),
            'responded_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.client-request-alerts.index')
            ->with('success', 'La relance client a ete mise a jour.');
    }
}
