<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeReportController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = (string) $request->query('employee', 'all');
        $status = (string) $request->query('status', 'all');
        $date = trim((string) $request->query('date', ''));

        $reports = EmployeeReport::query()
            ->with('user')
            ->when($employeeId !== 'all', fn ($query) => $query->where('user_id', (int) $employeeId))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($date !== '', fn ($query) => $query->whereDate('report_date', $date))
            ->latest('report_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        EmployeeReport::query()
            ->whereNull('admin_seen_at')
            ->update(['admin_seen_at' => now()]);

        return view('admin.employee_reports.index', [
            'reports' => $reports,
            'employees' => User::query()->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get(['id', 'name']),
            'employeeId' => $employeeId,
            'status' => $status,
            'date' => $date,
            'statuses' => EmployeeReport::availableStatuses(),
        ]);
    }

    public function show(EmployeeReport $employeeReport)
    {
        if (is_null($employeeReport->admin_seen_at)) {
            $employeeReport->update(['admin_seen_at' => now()]);
        }

        return view('admin.employee_reports.show', [
            'report' => $employeeReport->load(['user', 'reviewer']),
            'statuses' => EmployeeReport::availableStatuses(),
        ]);
    }

    public function review(Request $request, EmployeeReport $employeeReport)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(EmployeeReport::availableStatuses()))],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $payload = [
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_by' => auth()->id(),
            'admin_seen_at' => $employeeReport->admin_seen_at ?: now(),
        ];

        $payload['reviewed_at'] = in_array($validated['status'], [
            EmployeeReport::STATUS_REVIEWED,
            EmployeeReport::STATUS_VALIDATED,
        ], true) ? now() : null;

        $employeeReport->update($payload);

        return redirect()
            ->route('admin.employee-reports.show', $employeeReport)
            ->with('success', 'Le rapport employe a ete mis a jour.');
    }
}
