<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeInternalRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeInternalRequestController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = (string) $request->query('employee', 'all');
        $status = (string) $request->query('status', 'all');
        $category = (string) $request->query('category', 'all');

        $requests = EmployeeInternalRequest::query()
            ->with('user')
            ->when($employeeId !== 'all', fn ($query) => $query->where('user_id', (int) $employeeId))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($category !== 'all', fn ($query) => $query->where('category', $category))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        EmployeeInternalRequest::query()
            ->whereNull('admin_seen_at')
            ->update(['admin_seen_at' => now()]);

        return view('admin.employee_internal_requests.index', [
            'requests' => $requests,
            'employees' => User::query()->where('role', User::ROLE_EMPLOYEE)->orderBy('name')->get(['id', 'name']),
            'employeeId' => $employeeId,
            'status' => $status,
            'category' => $category,
            'statuses' => EmployeeInternalRequest::availableStatuses(),
            'categories' => EmployeeInternalRequest::availableCategories(),
        ]);
    }

    public function edit(EmployeeInternalRequest $employeeInternalRequest)
    {
        if (is_null($employeeInternalRequest->admin_seen_at)) {
            $employeeInternalRequest->update(['admin_seen_at' => now()]);
        }

        return view('admin.employee_internal_requests.edit', [
            'requestItem' => $employeeInternalRequest->load(['user', 'responder']),
            'statuses' => EmployeeInternalRequest::availableStatuses(),
        ]);
    }

    public function update(Request $request, EmployeeInternalRequest $employeeInternalRequest)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(EmployeeInternalRequest::availableStatuses()))],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $employeeInternalRequest->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'],
            'admin_seen_at' => $employeeInternalRequest->admin_seen_at ?: now(),
            'responded_at' => now(),
            'responded_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.employee-internal-requests.index')
            ->with('success', 'La demande RH interne a ete mise a jour.');
    }
}
