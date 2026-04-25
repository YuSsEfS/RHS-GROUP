<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeInternalRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InternalRequestController extends Controller
{
    public function index()
    {
        return view('employee.internal_requests.index', [
            'user' => auth()->user(),
            'requests' => auth()->user()
                ->employeeInternalRequests()
                ->latest()
                ->get(),
            'categories' => EmployeeInternalRequest::availableCategories(),
            'statuses' => EmployeeInternalRequest::availableStatuses(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(EmployeeInternalRequest::availableCategories()))],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        auth()->user()->employeeInternalRequests()->create($validated);

        return redirect()
            ->route('employee.internal-requests.index')
            ->with('success', 'Votre demande RH a ete envoyee.');
    }
}
