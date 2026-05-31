<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use App\Models\Cv;
use App\Models\EmployeeReport;
use App\Models\EmployeeInternalRequest;
use App\Models\EmployeeLeaveRequest;
use App\Models\ExternalCvBatch;
use App\Models\Meeting;
use App\Models\RhResource;
use App\Models\RecruitmentRequest;
use App\Services\SidebarNotificationService;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $canViewRecruitmentAssignments = $user->hasAnyPermission([
            'recruitment_requests',
            'recruitment_assignments_view',
        ]);
        $canUpdateRecruitmentAssignments = $user->hasPermission('recruitment_assignments_update');
        $canSeeClientAlerts = $user->hasAnyPermission([
            'recruitment_requests',
            'client_alerts_view',
        ]);
        $canViewCvBank = $user->hasAnyPermission(['cv_bank', 'cv_bank_manage']);
        $canManageCvBank = $user->hasPermission('cv_bank_manage');
        $canViewExternalCvs = $user->hasAnyPermission(['external_cvs', 'external_cvs_manage']);
        $canManageExternalCvs = $user->hasPermission('external_cvs_manage');
        $canViewMeetings = $user->hasAnyPermission(['meetings_view', 'meetings_manage']);
        $canViewRhResources = $user->hasAnyPermission(['rh_resources_view', 'rh_resources_manage']);

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
            'assignedRequestsCount' => $canViewRecruitmentAssignments
                ? $user->assignedRecruitmentRequests()
                    ->whereNotNull('client_user_id')
                    ->count()
                : 0,
            'assignedInProgressCount' => $canViewRecruitmentAssignments
                ? $user->assignedRecruitmentRequests()
                    ->whereNotNull('client_user_id')
                    ->where('assignment_status', RecruitmentRequest::ASSIGNMENT_STATUS_IN_PROGRESS)
                    ->count()
                : 0,
            'assignedCompletedCount' => $canViewRecruitmentAssignments
                ? $user->assignedRecruitmentRequests()
                    ->whereNotNull('client_user_id')
                    ->where('assignment_status', RecruitmentRequest::ASSIGNMENT_STATUS_COMPLETED)
                    ->count()
                : 0,
            'assignedRequests' => $canViewRecruitmentAssignments
                ? $user->assignedRecruitmentRequests()
                    ->whereNotNull('client_user_id')
                    ->latest()
                    ->limit(4)
                    ->get()
                : collect(),
            'clientAlertsCount' => $canSeeClientAlerts && Schema::hasTable('client_request_alerts')
                ? ClientRequestAlert::query()
                    ->whereHas('recruitmentRequest', function ($query) use ($user) {
                        $query->where('assigned_employee_id', $user->id);
                    })
                    ->whereNull('employee_seen_at')
                    ->count()
                : 0,
            'unreadMessagesCount' => (int) data_get(app(SidebarNotificationService::class)->forEmployee($user), 'items.conversations', 0),
            'canManageReports' => $user->hasPermission('employee_reports'),
            'canManageLeaveRequests' => $user->hasPermission('employee_leave_requests'),
            'canManageInternalRequests' => $user->hasPermission('employee_internal_requests'),
            'canSeeClientAlerts' => $canSeeClientAlerts,
            'canManageRecruitmentRequests' => $canViewRecruitmentAssignments,
            'canUpdateRecruitmentRequests' => $canUpdateRecruitmentAssignments,
            'canViewCvBank' => $canViewCvBank,
            'canManageCvBank' => $canManageCvBank,
            'canViewExternalCvs' => $canViewExternalCvs,
            'canManageExternalCvs' => $canManageExternalCvs,
            'canViewMeetings' => $canViewMeetings,
            'canViewRhResources' => $canViewRhResources,
            'cvBankCount' => $canViewCvBank && Schema::hasTable('cvs')
                ? Cv::query()
                    ->when(Schema::hasColumn('cvs', 'archived_at'), fn ($query) => $query->whereNull('archived_at'))
                    ->count()
                : 0,
            'externalBatchCount' => $canViewExternalCvs && Schema::hasTable('external_cv_batches')
                ? ExternalCvBatch::query()->count()
                : 0,
            'meetingCount' => $canViewMeetings && Schema::hasTable('meetings')
                ? Meeting::query()->forUser($user)->count()
                : 0,
            'rhResourceCount' => $canViewRhResources && Schema::hasTable('rh_resources')
                ? RhResource::query()->visibleFor($user)->count()
                : 0,
        ]);
    }
}
