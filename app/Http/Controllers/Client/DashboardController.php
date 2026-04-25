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

        $requestQuery = RecruitmentRequest::query()
            ->where('client_user_id', auth()->id());

        if (Schema::hasTable('client_request_alerts')) {
            $requestQuery
                ->withCount('clientAlerts')
                ->with(['clientAlerts' => fn ($query) => $query->latest()]);
        }

        return view('client.dashboard', [
            'user' => $user,
            'canManageRecruitmentRequests' => $user->hasPermission('recruitment_requests'),
            'requests' => $requestQuery->latest()->get(),
            'statuses' => RecruitmentRequest::availableStatuses(),
            'alertStatuses' => Schema::hasTable('client_request_alerts') ? ClientRequestAlert::availableStatuses() : [],
            'alertsEnabled' => Schema::hasTable('client_request_alerts'),
        ]);
    }
}
