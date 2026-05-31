<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use Illuminate\Http\Request;

class ClientRequestAlertController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasAnyPermission([
            'recruitment_requests',
            'client_alerts_view',
        ]), 403);

        $status = (string) $request->query('status', 'all');

        $alerts = ClientRequestAlert::query()
            ->with(['clientUser', 'recruitmentRequest'])
            ->whereHas('recruitmentRequest', function ($query) {
                $query->where('assigned_employee_id', auth()->id());
            })
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        ClientRequestAlert::query()
            ->whereHas('recruitmentRequest', function ($query) {
                $query->where('assigned_employee_id', auth()->id());
            })
            ->whereNull('employee_seen_at')
            ->update(['employee_seen_at' => now()]);

        return view('employee.client_alerts.index', [
            'alerts' => $alerts,
            'statuses' => ClientRequestAlert::availableStatuses(),
            'status' => $status,
            'user' => auth()->user(),
        ]);
    }
}
