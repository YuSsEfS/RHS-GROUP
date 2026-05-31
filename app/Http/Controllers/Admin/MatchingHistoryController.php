<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;

class MatchingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $requestsQuery = RecruitmentRequest::query()
            ->select([
                'id',
                'reference',
                'position_title',
                'client_name',
                'job_offer_id',
                'cv_folder_id',
                'matching_status',
                'matching_job_status',
                'matching_started_at',
                'matching_finished_at',
                'matching_completed_at',
                'matching_viewed_at',
                'updated_at',
                'created_at',
            ])
            ->with([
                'jobOffer:id,title',
                'folder:id,name',
            ])
            ->withCount([
                'matches',
                'matches as selected_matches_count' => function ($query) {
                    $query->where('selected', true);
                },
            ])
            ->where(function ($query) {
                $query->has('matches')
                    ->orWhereNotNull('matching_status')
                    ->orWhereNotNull('matching_job_status');
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('reference', 'like', "%{$q}%")
                        ->orWhere('position_title', 'like', "%{$q}%")
                        ->orWhere('client_name', 'like', "%{$q}%")
                        ->orWhereHas('jobOffer', function ($offerQuery) use ($q) {
                            $offerQuery->where('title', 'like', "%{$q}%");
                        });
                });
            })
            ->when($status !== '' && $status !== 'all', function ($query) use ($status) {
                $query->where(function ($subQuery) use ($status) {
                    match ($status) {
                        RecruitmentRequest::MATCHING_STATUS_PENDING => $subQuery
                            ->where('matching_status', RecruitmentRequest::MATCHING_STATUS_PENDING)
                            ->orWhere(function ($pendingQuery) {
                                $pendingQuery
                                    ->whereNull('matching_status')
                                    ->where(function ($jobQuery) {
                                        $jobQuery->whereNull('matching_job_status')
                                            ->orWhere('matching_job_status', RecruitmentRequest::JOB_STATUS_PENDING);
                                    });
                            }),
                        RecruitmentRequest::MATCHING_STATUS_PROCESSING => $subQuery
                            ->where('matching_status', RecruitmentRequest::MATCHING_STATUS_PROCESSING)
                            ->orWhere('matching_job_status', RecruitmentRequest::JOB_STATUS_RUNNING),
                        RecruitmentRequest::MATCHING_STATUS_COMPLETED => $subQuery
                            ->where('matching_status', RecruitmentRequest::MATCHING_STATUS_COMPLETED)
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
                            }),
                        RecruitmentRequest::MATCHING_STATUS_FAILED => $subQuery
                            ->where('matching_status', RecruitmentRequest::MATCHING_STATUS_FAILED)
                            ->orWhere('matching_job_status', RecruitmentRequest::JOB_STATUS_FAILED),
                        default => $subQuery->where('matching_status', $status),
                    };
                });
            })
            ->latest('updated_at');

        $requests = $requestsQuery->simplePaginate(15)->withQueryString();

        return view('admin.matching_history.index', [
            'requests' => $requests,
            'q' => $q,
            'status' => $status,
            'statusLabels' => RecruitmentRequest::availableMatchingStatuses(),
        ]);
    }
}
