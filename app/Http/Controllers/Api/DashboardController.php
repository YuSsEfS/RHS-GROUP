<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\AdminEmployeeConversation;
use App\Models\Cv;
use App\Models\CvMatch;
use App\Models\ExternalCvBatch;
use App\Models\JobApplication;
use App\Models\Meeting;
use App\Models\RecruitmentRequest;
use App\Models\RhResource;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesUserTimezone;

    public function show(Request $request)
    {
        $user = $request->user();
        $timezone = $this->userTimezone($request);
        $requests = $this->visibleRequests($user);

        $metrics = [
            [
                'label' => 'Demandes recrutement',
                'value' => (clone $requests)->count(),
            ],
            [
                'label' => 'Matchings en cours',
                'value' => (clone $requests)
                    ->whereIn('matching_status', [
                        RecruitmentRequest::MATCHING_STATUS_PENDING,
                        RecruitmentRequest::MATCHING_STATUS_PROCESSING,
                    ])
                    ->count(),
            ],
            [
                'label' => 'Candidatures',
                'value' => $user->isAdmin() ? JobApplication::count() : 0,
            ],
            [
                'label' => 'Ressources RH',
                'value' => RhResource::query()->visibleFor($user)->count(),
            ],
            [
                'label' => 'Reunions',
                'value' => Meeting::query()
                    ->when(!$user->isAdmin(), function ($query) use ($user) {
                        $query->whereHas('participants', fn ($participants) => $participants->where('user_id', $user->id));
                    })
                    ->count(),
            ],
            [
                'label' => 'Messages',
                'value' => AdminEmployeeConversation::query()->forParticipant($user)->count(),
            ],
            [
                'label' => 'CV Bank',
                'value' => $this->canSeeCvBank($user) ? Cv::query()->whereNull('archived_at')->count() : 0,
            ],
            [
                'label' => 'Base externe',
                'value' => $this->canSeeCvBank($user) ? ExternalCvBatch::query()->count() : 0,
            ],
            [
                'label' => 'Candidats selectionnes',
                'value' => $user->isAdmin() ? CvMatch::query()->where('selected', true)->count() : (clone $requests)->whereHas('matches', fn ($query) => $query->where('selected', true))->count(),
            ],
        ];

        $activity = (clone $requests)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (RecruitmentRequest $item) => [
                'id' => $item->id,
                'title' => $item->position_title ?: 'Demande recrutement',
                'body' => ($item->client_name ?: 'RHS GROUP').' - '.($item->request_status ?: 'Nouveau'),
                'status' => $item->request_status,
                'date' => $this->localDate($item->updated_at, $timezone),
            ])
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'metrics' => $metrics,
            'activity' => $activity,
            'stats' => [
                'requests' => [
                    'total' => (clone $requests)->count(),
                    'pending' => (clone $requests)->where('request_status', RecruitmentRequest::STATUS_PENDING)->count(),
                    'completed_matching' => (clone $requests)->where('matching_status', RecruitmentRequest::MATCHING_STATUS_COMPLETED)->count(),
                ],
                'matching' => [
                    'pending' => (clone $requests)->where('matching_status', RecruitmentRequest::MATCHING_STATUS_PENDING)->count(),
                    'processing' => (clone $requests)->where('matching_status', RecruitmentRequest::MATCHING_STATUS_PROCESSING)->count(),
                    'completed' => (clone $requests)->where('matching_status', RecruitmentRequest::MATCHING_STATUS_COMPLETED)->count(),
                    'selected' => $user->isAdmin() ? CvMatch::query()->where('selected', true)->count() : (clone $requests)->whereHas('matches', fn ($query) => $query->where('selected', true))->count(),
                ],
                'library' => [
                    'cv_bank' => $this->canSeeCvBank($user) ? Cv::query()->whereNull('archived_at')->count() : 0,
                    'archived_cvs' => $this->canSeeCvBank($user) ? Cv::query()->whereNotNull('archived_at')->count() : 0,
                    'external_batches' => $this->canSeeCvBank($user) ? ExternalCvBatch::query()->count() : 0,
                    'external_processing' => $this->canSeeCvBank($user) ? ExternalCvBatch::query()->whereIn('status', [ExternalCvBatch::STATUS_PENDING, ExternalCvBatch::STATUS_PROCESSING])->count() : 0,
                ],
                'planning' => [
                    'meetings' => Meeting::query()
                        ->when(!$user->isAdmin(), fn ($query) => $query->whereHas('participants', fn ($participants) => $participants->where('user_id', $user->id)))
                        ->count(),
                    'upcoming' => Meeting::query()
                        ->when(!$user->isAdmin(), fn ($query) => $query->whereHas('participants', fn ($participants) => $participants->where('user_id', $user->id)))
                        ->where('meeting_date', '>=', $this->localNow($request)->toDateString())
                        ->count(),
                ],
            ],
            'timezone' => $timezone,
        ]);
    }

    private function visibleRequests(User $user)
    {
        return RecruitmentRequest::query()
            ->when($user->hasRole(User::ROLE_CLIENT), fn ($query) => $query->where('client_user_id', $user->id))
            ->when($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR]), function ($query) use ($user) {
                $query->where('assigned_employee_id', $user->id);
            });
    }

    private function canSeeCvBank(User $user): bool
    {
        return $user->isAdmin() || $user->hasAnyPermission(['cv_bank', 'external_cvs']);
    }
}
