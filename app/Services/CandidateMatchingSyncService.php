<?php

namespace App\Services;

use App\Jobs\SyncCvMatchesJob;
use App\Models\Cv;
use App\Models\RecruitmentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CandidateMatchingSyncService
{
    public function dispatchForCvId(?int $cvId, string $reason = 'cv_sync'): int
    {
        if (!$cvId) {
            return 0;
        }

        $cv = Cv::find($cvId);

        return $cv ? $this->dispatchForCv($cv, $reason) : 0;
    }

    public function dispatchForCv(Cv $cv, string $reason = 'cv_sync'): int
    {
        if (empty($cv->structured_profile)) {
            return 0;
        }

        $requestIds = $this->candidateRecruitmentRequests($cv);

        if (empty($requestIds)) {
            return 0;
        }

        $requestVersion = RecruitmentRequest::query()
            ->whereIn('id', $requestIds)
            ->orderBy('id')
            ->get(['id', 'updated_at'])
            ->map(fn (RecruitmentRequest $request) => $request->id . ':' . optional($request->updated_at)->timestamp)
            ->implode('|');
        $lockKey = 'matching-sync:cv:' . $cv->id . ':' . md5(implode(',', $requestIds) . '|' . $requestVersion);

        if (!Cache::add($lockKey, true, now()->addMinutes(3))) {
            return 0;
        }

        SyncCvMatchesJob::dispatch($cv->id, $requestIds, $reason)->afterCommit();
        DB::afterCommit(fn () => app(MatchingWorkerLauncher::class)->start(
            SyncCvMatchesJob::queueNameFor($cv->id)
        ));

        return count($requestIds);
    }

    private function candidateRecruitmentRequests(Cv $cv): array
    {
        $query = RecruitmentRequest::query()
            ->whereNotNull('ai_normalized_requirements')
            ->where(function ($query) {
                $query
                    ->whereIn('request_status', [
                        RecruitmentRequest::STATUS_PENDING,
                        RecruitmentRequest::STATUS_UNDER_REVIEW,
                        RecruitmentRequest::STATUS_MATCHING_IN_PROGRESS,
                        RecruitmentRequest::STATUS_SHORTLISTED,
                    ])
                    ->orWhereIn('pipeline_stage', [
                        RecruitmentRequest::PIPELINE_STAGE_ANALYSIS,
                        RecruitmentRequest::PIPELINE_STAGE_MATCHING,
                        RecruitmentRequest::PIPELINE_STAGE_SHORTLIST,
                    ])
                    ->orWhereIn('matching_status', [
                        RecruitmentRequest::MATCHING_STATUS_PENDING,
                        RecruitmentRequest::MATCHING_STATUS_COMPLETED,
                        RecruitmentRequest::MATCHING_STATUS_FAILED,
                    ]);
            })
            ->latest('updated_at')
            ->limit(80);

        if (Schema::hasColumn('recruitment_requests', 'cv_folder_id') && Schema::hasColumn('cvs', 'cv_folder_id')) {
            $folderId = (int) ($cv->cv_folder_id ?? 0);

            if ($folderId > 0) {
                $query->where(function ($query) use ($folderId) {
                    $query
                        ->whereNull('cv_folder_id')
                        ->orWhere('cv_folder_id', $folderId)
                        ->orWhereJsonContains('ai_normalized_requirements->cv_folder_ids', $folderId);
                });
            }
        }

        return $query
            ->get(['id', 'ai_normalized_requirements', 'cv_folder_id'])
            ->filter(fn (RecruitmentRequest $request) => $this->requestCanUseCv($request, $cv))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function requestCanUseCv(RecruitmentRequest $request, Cv $cv): bool
    {
        $requirements = is_array($request->ai_normalized_requirements)
            ? $request->ai_normalized_requirements
            : [];

        $folderIds = collect($requirements['cv_folder_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($folderIds->isEmpty() && !empty($requirements['cv_folder_id'])) {
            $folderIds->push((int) $requirements['cv_folder_id']);
        }

        if ($folderIds->isEmpty() && !empty($request->cv_folder_id)) {
            $folderIds->push((int) $request->cv_folder_id);
        }

        if ($folderIds->isEmpty()) {
            return true;
        }

        return $cv->cv_folder_id && $folderIds->contains((int) $cv->cv_folder_id);
    }
}
