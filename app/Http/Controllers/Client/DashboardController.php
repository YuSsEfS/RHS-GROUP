<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use App\Models\RecruitmentRequest;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $requestQuery = RecruitmentRequest::query()->where('client_user_id', auth()->id());

        return view('client.dashboard', [
            'user' => $user,
            'canManageRecruitmentRequests' => $user->hasPermission('recruitment_requests'),
            'requestsCount' => (clone $requestQuery)->count(),
            'requestsInProgress' => (clone $requestQuery)
                ->whereIn('request_status', [
                    RecruitmentRequest::STATUS_PENDING,
                    RecruitmentRequest::STATUS_UNDER_REVIEW,
                    RecruitmentRequest::STATUS_MATCHING_IN_PROGRESS,
                ])->count(),
            'requestsCompleted' => (clone $requestQuery)
                ->whereIn('request_status', [
                    RecruitmentRequest::STATUS_SHORTLISTED,
                    RecruitmentRequest::STATUS_COMPLETED,
                ])->count(),
            'alertsCount' => Schema::hasTable('client_request_alerts')
                ? (clone $requestQuery)->withCount('clientAlerts')->get()->sum('client_alerts_count')
                : 0,
            'latestRequests' => (clone $requestQuery)
                ->withCount('clientAlerts')
                ->latest()
                ->limit(3)
                ->get(),
            'statuses' => RecruitmentRequest::availableStatuses(),
            'pipelineStages' => RecruitmentRequest::availablePipelineStages(),
            'alertStatuses' => Schema::hasTable('client_request_alerts') ? ClientRequestAlert::availableStatuses() : [],
            'alertsEnabled' => Schema::hasTable('client_request_alerts'),
        ]);
    }
}
