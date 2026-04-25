<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use Illuminate\Http\Request;

class ClientRequestAlertController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'all');

        $alerts = ClientRequestAlert::query()
            ->with(['clientUser', 'recruitmentRequest'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        ClientRequestAlert::query()
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
