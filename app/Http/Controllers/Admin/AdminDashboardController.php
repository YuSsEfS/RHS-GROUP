<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use App\Models\ContactMessage;
use App\Models\Cv;
use App\Models\EmployeeInternalRequest;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeReport;
use App\Models\JobOffer;
use App\Models\JobApplication;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'offersCount' => JobOffer::count(),
            'appsUnread'  => JobApplication::whereNull('admin_seen_at')->count(),
            'msgsUnread'  => ContactMessage::where('is_read', false)->count(),
            'pendingClientRequests' => RecruitmentRequest::query()
                ->whereNotNull('client_user_id')
                ->whereIn('request_status', [
                    RecruitmentRequest::STATUS_PENDING,
                    RecruitmentRequest::STATUS_UNDER_REVIEW,
                ])
                ->count(),
            'activeRecruitmentRequests' => RecruitmentRequest::query()
                ->whereIn('request_status', [
                    RecruitmentRequest::STATUS_PENDING,
                    RecruitmentRequest::STATUS_UNDER_REVIEW,
                    RecruitmentRequest::STATUS_MATCHING_IN_PROGRESS,
                    RecruitmentRequest::STATUS_SHORTLISTED,
                ])
                ->count(),
            'totalUsers' => User::count(),
            'pendingUserApprovals' => User::where('status', User::STATUS_PENDING)->count(),
            'pendingClientAlerts' => Schema::hasTable('client_request_alerts')
                ? ClientRequestAlert::query()->where('status', ClientRequestAlert::STATUS_NEW)->count()
                : 0,
            'cvBankCount' => Cv::count(),
            'pendingEmployeeReports' => EmployeeReport::query()
                ->where('status', EmployeeReport::STATUS_PENDING)
                ->count(),
            'pendingLeaveRequests' => EmployeeLeaveRequest::query()
                ->where('status', EmployeeLeaveRequest::STATUS_PENDING)
                ->count(),
            'openInternalRequests' => EmployeeInternalRequest::query()
                ->whereIn('status', [
                    EmployeeInternalRequest::STATUS_PENDING,
                    EmployeeInternalRequest::STATUS_IN_PROGRESS,
                ])
                ->count(),
            'clientRequestStatusChart' => RecruitmentRequest::query()
                ->whereNotNull('client_user_id')
                ->selectRaw('request_status, COUNT(*) as total')
                ->groupBy('request_status')
                ->pluck('total', 'request_status')
                ->all(),
            'leaveStatusChart' => EmployeeLeaveRequest::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
            'employeeRequestTypeChart' => EmployeeInternalRequest::query()
                ->selectRaw('category, COUNT(*) as total')
                ->groupBy('category')
                ->pluck('total', 'category')
                ->all(),
        ]);
    }
}
