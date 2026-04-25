<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    public function index()
    {
        return view('employee.leave_requests.index', [
            'user' => auth()->user(),
            'leaveRequests' => auth()->user()
                ->employeeLeaveRequests()
                ->latest('start_date')
                ->latest('id')
                ->get(),
            'types' => EmployeeLeaveRequest::availableTypes(),
            'statuses' => EmployeeLeaveRequest::availableStatuses(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type' => ['required', Rule::in(array_keys(EmployeeLeaveRequest::availableTypes()))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string'],
        ]);

        auth()->user()->employeeLeaveRequests()->create($validated);

        return redirect()
            ->route('employee.leave-requests.index')
            ->with('success', 'Votre demande de conge a ete envoyee.');
    }

    public function cancel(EmployeeLeaveRequest $leaveRequest)
    {
        abort_unless($leaveRequest->user_id === auth()->id(), 403);
        abort_unless($leaveRequest->status === EmployeeLeaveRequest::STATUS_PENDING, 422);

        $leaveRequest->update([
            'status' => EmployeeLeaveRequest::STATUS_CANCELLED,
        ]);

        return redirect()
            ->route('employee.leave-requests.index')
            ->with('success', 'Votre demande de conge a ete annulee.');
    }
}
