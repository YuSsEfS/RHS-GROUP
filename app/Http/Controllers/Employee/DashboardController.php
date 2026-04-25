<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use App\Models\EmployeeReport;
use App\Models\EmployeeInternalRequest;
use App\Models\EmployeeLeaveRequest;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('employee.dashboard', [
            'user' => $user,
            'reportCount' => $user->employeeReports()->count(),
            'reportMonthCount' => $user->employeeReports()
                ->whereMonth('report_date', now()->month)
                ->whereYear('report_date', now()->year)
                ->count(),
            'pendingLeaveCount' => $user->employeeLeaveRequests()
                ->where('status', EmployeeLeaveRequest::STATUS_PENDING)
                ->count(),
            'openInternalRequestCount' => $user->employeeInternalRequests()
                ->whereIn('status', [
                    EmployeeInternalRequest::STATUS_PENDING,
                    EmployeeInternalRequest::STATUS_IN_PROGRESS,
                ])
                ->count(),
            'validatedReportCount' => $user->employeeReports()
                ->where('status', EmployeeReport::STATUS_VALIDATED)
                ->count(),
            'recentReports' => $user->employeeReports()
                ->latest('report_date')
                ->latest('id')
                ->limit(3)
                ->get(),
            'recentLeaveRequests' => $user->employeeLeaveRequests()
                ->latest('start_date')
                ->latest('id')
                ->limit(3)
                ->get(),
            'recentInternalRequests' => $user->employeeInternalRequests()
                ->latest()
                ->limit(3)
                ->get(),
            'clientAlertsCount' => $user->hasPermission('recruitment_requests') && Schema::hasTable('client_request_alerts')
                ? ClientRequestAlert::query()->whereNull('employee_seen_at')->count()
                : 0,
            'canManageReports' => $user->hasPermission('employee_reports'),
            'canManageLeaveRequests' => $user->hasPermission('employee_leave_requests'),
            'canManageInternalRequests' => $user->hasPermission('employee_internal_requests'),
            'canSeeClientAlerts' => $user->hasPermission('recruitment_requests'),
        ]);
    }
}
