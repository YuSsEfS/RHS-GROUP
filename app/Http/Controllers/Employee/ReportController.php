<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index()
    {
        return view('employee.reports.index', [
            'user' => auth()->user(),
            'reports' => auth()->user()
                ->employeeReports()
                ->latest('report_date')
                ->latest('id')
                ->get(),
            'types' => EmployeeReport::availableTypes(),
            'statuses' => EmployeeReport::availableStatuses(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_type' => ['required', Rule::in(array_keys(EmployeeReport::availableTypes()))],
            'report_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'summary' => ['required', 'string'],
            'achievements' => ['nullable', 'string'],
            'blockers' => ['nullable', 'string'],
            'next_steps' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('private/employee-reports', 'local');
        }

        auth()->user()->employeeReports()->create([
            ...$validated,
            'status' => EmployeeReport::STATUS_PENDING,
        ]);

        return redirect()
            ->route('employee.reports.index')
            ->with('success', 'Votre rapport a ete envoye avec succes.');
    }
}
