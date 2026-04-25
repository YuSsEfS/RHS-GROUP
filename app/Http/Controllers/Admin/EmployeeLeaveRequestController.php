<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeLeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = (string) $request->query('employee', 'all');
        $status = (string) $request->query('status', 'all');
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        $leaveRequests = EmployeeLeaveRequest::query()
            ->with('user')
            ->when($employeeId !== 'all', fn ($query) => $query->where('user_id', (int) $employeeId))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($from !== '', fn ($query) => $query->whereDate('start_date', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('end_date', '<=', $to))
            ->latest('start_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        EmployeeLeaveRequest::query()
            ->whereNull('admin_seen_at')
            ->update(['admin_seen_at' => now()]);

        return view('admin.employee_leave_requests.index', [
            'leaveRequests' => $leaveRequests,
            'employees' => User::query()->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get(['id', 'name']),
            'employeeId' => $employeeId,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'statuses' => EmployeeLeaveRequest::availableStatuses(),
        ]);
    }

    public function edit(EmployeeLeaveRequest $employeeLeaveRequest)
    {
        if (is_null($employeeLeaveRequest->admin_seen_at)) {
            $employeeLeaveRequest->update(['admin_seen_at' => now()]);
        }

        return view('admin.employee_leave_requests.edit', [
            'leaveRequest' => $employeeLeaveRequest->load(['user', 'decider']),
            'statuses' => EmployeeLeaveRequest::availableStatuses(),
        ]);
    }

    public function update(Request $request, EmployeeLeaveRequest $employeeLeaveRequest)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(EmployeeLeaveRequest::availableStatuses()))],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $employeeLeaveRequest->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'],
            'admin_seen_at' => $employeeLeaveRequest->admin_seen_at ?: now(),
            'decided_at' => now(),
            'decided_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.employee-leave-requests.index')
            ->with('success', 'La demande de conge a ete mise a jour.');
    }
}
