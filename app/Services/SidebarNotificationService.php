<?php

namespace App\Services;

use App\Models\ClientRequestAlert;
use App\Models\AdminEmployeeConversation;
use App\Models\CvImportBatch;
use App\Models\EmployeeInternalRequest;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeReport;
use App\Models\ExternalCvBatch;
use App\Models\JobApplication;
use App\Models\MeetingParticipant;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class SidebarNotificationService
{
    public function clearFor(User $user): void
    {
        Cache::forget('sidebar.notifications.admin.' . $user->id);
        Cache::forget('sidebar.notifications.employee.' . $user->id);
    }

    public function forAdmin(User $user): array
    {
        $items = Cache::remember('sidebar.notifications.admin.' . $user->id, now()->addSeconds(2), function () use ($user) {
            return [
            'users' => $user->isAdmin()
                ? User::query()->where('status', User::STATUS_PENDING)->count()
                : 0,
            'employee_reports' => Schema::hasTable('employee_reports')
                ? EmployeeReport::query()->whereNull('admin_seen_at')->count()
                : 0,
            'employee_assignments' => Schema::hasTable('recruitment_requests')
                ? RecruitmentRequest::query()
                    ->whereNotNull('client_user_id')
                    ->whereNotNull('assigned_employee_id')
                    ->whereNull('assignment_seen_at')
                    ->count()
                : 0,
            'employee_leave_requests' => Schema::hasTable('employee_leave_requests')
                ? EmployeeLeaveRequest::query()->whereNull('admin_seen_at')->count()
                : 0,
            'employee_internal_requests' => Schema::hasTable('employee_internal_requests')
                ? EmployeeInternalRequest::query()->whereNull('admin_seen_at')->count()
                : 0,
            'client_requests' => Schema::hasTable('recruitment_requests')
                ? RecruitmentRequest::query()
                    ->whereNotNull('client_user_id')
                    ->whereNull('admin_seen_at')
                    ->count()
                : 0,
            'client_alerts' => Schema::hasTable('client_request_alerts')
                ? ClientRequestAlert::query()->whereNull('admin_seen_at')->count()
                : 0,
            'applications' => Schema::hasTable('job_applications')
                ? JobApplication::query()->where('is_read', false)->count()
                : 0,
            'matching_history' => $this->countUnreadMatchingResults(),
            'cv_imports' => Schema::hasTable('cv_import_batches')
                ? CvImportBatch::query()
                    ->whereIn('status', [
                        CvImportBatch::STATUS_PENDING,
                        CvImportBatch::STATUS_PROCESSING,
                    ])
                    ->count()
                : 0,
            'external_batches' => Schema::hasTable('external_cv_batches')
                ? ExternalCvBatch::query()
                    ->whereIn('processing_status', [
                        ExternalCvBatch::PROCESSING_STATUS_PENDING,
                        ExternalCvBatch::PROCESSING_STATUS_RUNNING,
                    ])
                    ->count()
                : 0,
            'conversations' => Schema::hasTable('admin_employee_conversations')
                ? $this->countUnreadConversations($user)
                : 0,
            'meetings' => Schema::hasTable('meeting_participants')
                ? MeetingParticipant::query()
                    ->where('user_id', $user->id)
                    ->whereNull('notification_read_at')
                    ->count()
                : 0,
            ];
        });

        $visibleItems = [
            'users' => $items['users'],
            'employee_reports' => $items['employee_reports'],
            'employee_assignments' => $items['employee_assignments'],
            'employee_leave_requests' => $items['employee_leave_requests'],
            'employee_internal_requests' => $items['employee_internal_requests'],
            'client_requests' => $items['client_requests'],
            'client_alerts' => $items['client_alerts'],
            'applications' => $items['applications'],
            'matching_history' => $items['matching_history'],
            'cv_imports' => $items['cv_imports'],
            'external_batches' => $items['external_batches'],
            'conversations' => $items['conversations'],
            'meetings' => $items['meetings'],
        ];

        return [
            'items' => $visibleItems,
            'groups' => [
                'employees' => $visibleItems['employee_reports']
                    + $visibleItems['employee_assignments']
                    + $visibleItems['employee_leave_requests']
                    + $visibleItems['employee_internal_requests'],
                'clients' => $visibleItems['client_requests']
                    + $visibleItems['client_alerts'],
                'recruitment' => $visibleItems['matching_history']
                    + $visibleItems['applications']
                    + $visibleItems['cv_imports']
                    + $visibleItems['external_batches'],
                'platform' => 0,
            ],
            'client_register' => [
                'enabled' => Route::has('client.register'),
                'url' => Route::has('client.register') ? route('client.register') : null,
            ],
        ];
    }

    public function forEmployee(User $user): array
    {
        return Cache::remember('sidebar.notifications.employee.' . $user->id, now()->addSeconds(2), function () use ($user) {
        $canViewAssignments = $user->hasAnyPermission([
            'recruitment_requests',
            'recruitment_assignments_view',
        ]);

        $canViewClientAlerts = $user->hasAnyPermission([
            'recruitment_requests',
            'client_alerts_view',
        ]);

        return [
            'items' => [
                'assigned_requests' => $canViewAssignments
                    ? RecruitmentRequest::query()
                        ->where('assigned_employee_id', $user->id)
                        ->whereNotNull('client_user_id')
                        ->whereNull('assignment_seen_at')
                        ->count()
                    : 0,
                'client_alerts' => $canViewClientAlerts && Schema::hasTable('client_request_alerts')
                    ? ClientRequestAlert::query()
                        ->whereHas('recruitmentRequest', function ($query) use ($user) {
                            $query->where('assigned_employee_id', $user->id);
                        })
                        ->whereNull('employee_seen_at')
                        ->count()
                    : 0,
                'conversations' => Schema::hasTable('admin_employee_conversations')
                    ? $this->countUnreadConversations($user)
                    : 0,
                'meetings' => $user->hasAnyPermission(['meetings_view', 'meetings_manage']) && Schema::hasTable('meeting_participants')
                    ? MeetingParticipant::query()
                        ->where('user_id', $user->id)
                        ->whereNull('notification_read_at')
                        ->count()
                    : 0,
            ],
        ];
        });
    }

    private function countUnreadMatchingResults(): int
    {
        if (
            !Schema::hasTable('recruitment_requests')
            || !Schema::hasColumn('recruitment_requests', 'matching_status')
            || !Schema::hasColumn('recruitment_requests', 'matching_finished_at')
            || !Schema::hasColumn('recruitment_requests', 'matching_viewed_at')
        ) {
            return 0;
        }

        return RecruitmentRequest::query()
            ->where(function ($query) {
                $query->where('matching_status', RecruitmentRequest::MATCHING_STATUS_COMPLETED)
                    ->orWhere('matching_job_status', RecruitmentRequest::JOB_STATUS_DONE)
                    ->orWhere(function ($completedQuery) {
                        $completedQuery
                            ->whereHas('matches')
                            ->where(function ($stateQuery) {
                                $stateQuery
                                    ->whereNull('matching_status')
                                    ->orWhere('matching_status', RecruitmentRequest::MATCHING_STATUS_PENDING);
                            })
                            ->where(function ($jobStateQuery) {
                                $jobStateQuery
                                    ->whereNull('matching_job_status')
                                    ->orWhere('matching_job_status', RecruitmentRequest::JOB_STATUS_PENDING)
                                    ->orWhere('matching_job_status', RecruitmentRequest::JOB_STATUS_DONE);
                            });
                    });
            })
            ->where(function ($query) {
                $query->whereNull('matching_viewed_at')
                    ->orWhere(function ($comparison) {
                        $comparison
                            ->whereNotNull('matching_finished_at')
                            ->whereColumn('matching_finished_at', '>', 'matching_viewed_at');
                    })
                    ->orWhere(function ($comparison) {
                        $comparison
                            ->whereNull('matching_finished_at')
                            ->whereNotNull('matching_completed_at')
                            ->whereColumn('matching_completed_at', '>', 'matching_viewed_at');
                    })
                    ->orWhere(function ($comparison) {
                        $comparison
                            ->whereNull('matching_finished_at')
                            ->whereNull('matching_completed_at')
                            ->whereHas('matches')
                            ->whereColumn('updated_at', '>', 'matching_viewed_at');
                    });
            })
            ->count();
    }

    private function countUnreadConversations(User $user): int
    {
        if (
            Schema::hasTable('admin_employee_conversation_participants')
            && Schema::hasTable('admin_employee_messages')
            && Schema::hasColumn('admin_employee_messages', 'seen_at')
        ) {
            return (int) DB::table('admin_employee_messages as messages')
                ->join('admin_employee_conversations as conversations', 'conversations.id', '=', 'messages.conversation_id')
                ->leftJoin('admin_employee_conversation_participants as participants', function ($join) use ($user) {
                    $join->on('participants.conversation_id', '=', 'messages.conversation_id')
                        ->where('participants.user_id', '=', $user->id);
                })
                ->where(function ($query) use ($user) {
                    $query->where('conversations.admin_user_id', $user->id)
                        ->orWhere('conversations.employee_user_id', $user->id)
                        ->orWhereNotNull('participants.id');
                })
                ->where('messages.sender_id', '!=', $user->id)
                ->where(function ($query) {
                    $query
                        ->where(function ($directQuery) {
                            $directQuery
                                ->where(function ($typeQuery) {
                                    $typeQuery->whereNull('conversations.conversation_type')
                                        ->orWhere('conversations.conversation_type', '!=', AdminEmployeeConversation::TYPE_GROUP);
                                })
                                ->whereNull('messages.seen_at');
                        })
                        ->orWhere(function ($groupQuery) {
                            $groupQuery
                                ->where('conversations.conversation_type', AdminEmployeeConversation::TYPE_GROUP)
                                ->where(function ($seenQuery) {
                                    $seenQuery->whereNull('participants.seen_at')
                                        ->orWhereColumn('messages.created_at', '>', 'participants.seen_at');
                                });
                        });
                })
                ->count();
        }

        return AdminEmployeeConversation::query()
            ->whereNotNull('last_message_at')
            ->where(function ($query) use ($user) {
                $query
                    ->where(function ($participantQuery) use ($user) {
                        $participantQuery
                            ->where('admin_user_id', $user->id)
                            ->where(function ($stateQuery) {
                                $stateQuery->whereNull('admin_seen_at')
                                    ->orWhereColumn('last_message_at', '>', 'admin_seen_at');
                            });
                    })
                    ->orWhere(function ($participantQuery) use ($user) {
                        $participantQuery
                            ->where('employee_user_id', $user->id)
                            ->where(function ($stateQuery) {
                                $stateQuery->whereNull('employee_seen_at')
                                    ->orWhereColumn('last_message_at', '>', 'employee_seen_at');
                            });
                    });
            })
            ->count();
    }
}
