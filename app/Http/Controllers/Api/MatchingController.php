<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\CvMatch;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MatchingController extends Controller
{
    use ResolvesUserTimezone;

    public function index(Request $request)
    {
        $timezone = $this->userTimezone($request);

        $items = $this->visibleRequests($request->user())
            ->where(function ($query) {
                $query
                    ->whereNotNull('matching_status')
                    ->orWhereNotNull('matching_job_status')
                    ->orWhereHas('matches');
            })
            ->withCount([
                'matches',
                'matches as selected_matches_count' => fn ($query) => $query->where('selected', true),
            ])
            ->with(['matches' => fn ($query) => $query->latest()->limit(1)])
            ->latest()
            ->paginate(20)
            ->through(fn (RecruitmentRequest $item) => $this->payload($item, $timezone));

        return response()->json($items);
    }

    public function show(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        abort_unless($this->canSee($request->user(), $recruitmentRequest), 403);

        $recruitmentRequest->loadCount([
            'matches',
            'matches as selected_matches_count' => fn ($query) => $query->where('selected', true),
        ])->load(['matches' => fn ($query) => $query
            ->with('cv:id,candidate_name,phone,email,original_filename,cv_folder_id')
            ->orderByDesc('score')
            ->limit(25)]);

        return response()->json([
            ...$this->payload($recruitmentRequest, $this->userTimezone($request)),
            'matches' => $recruitmentRequest->matches
                ->map(fn (CvMatch $match) => $this->matchPayload($match))
                ->values(),
        ]);
    }

    public function cancel(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin()
            || ($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR]) && $recruitmentRequest->isAssignedTo($user)),
            403
        );

        $recruitmentRequest->markMatchingCancelled();
        $recruitmentRequest->loadCount([
            'matches',
            'matches as selected_matches_count' => fn ($query) => $query->where('selected', true),
        ]);

        return response()->json($this->payload($recruitmentRequest, $this->userTimezone($request)));
    }

    private function visibleRequests(User $user)
    {
        return RecruitmentRequest::query()
            ->when($user->hasRole(User::ROLE_CLIENT), fn ($query) => $query->where('client_user_id', $user->id))
            ->when($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR]), function ($query) use ($user) {
                $query->where('assigned_employee_id', $user->id);
            });
    }

    private function canSee(User $user, RecruitmentRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole(User::ROLE_CLIENT)) {
            return (int) $request->client_user_id === (int) $user->id;
        }

        if ($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])) {
            return $request->isAssignedTo($user);
        }

        return false;
    }

    private function payload(RecruitmentRequest $request, string $timezone): array
    {
        $status = $request->resolveMatchingStatus() ?: RecruitmentRequest::MATCHING_STATUS_PENDING;
        $matchesCount = (int) ($request->matches_count ?? $request->matches()->count());
        $selectedCount = (int) ($request->selected_matches_count ?? $request->matches()->where('selected', true)->count());
        $target = max((int) $request->candidate_count, $matchesCount, 1);

        $progress = match ($status) {
            RecruitmentRequest::MATCHING_STATUS_COMPLETED,
            RecruitmentRequest::MATCHING_STATUS_FAILED,
            RecruitmentRequest::MATCHING_STATUS_CANCELLED => 100,
            RecruitmentRequest::MATCHING_STATUS_PROCESSING => min(99, (int) round(($matchesCount / $target) * 100)),
            default => 0,
        };

        $latestMatch = $request->relationLoaded('matches')
            ? $request->matches->first()
            : $request->matches()->latest()->first();

        return [
            'id' => $request->id,
            'title' => $request->position_title ?: 'Matching RH',
            'reference' => $request->reference,
            'client' => $request->client_name ?: 'RHS GROUP',
            'status' => RecruitmentRequest::availableMatchingStatuses()[$status] ?? $status,
            'raw_status' => $status,
            'progress' => $progress,
            'treated' => $matchesCount,
            'total' => $target,
            'selected' => $selectedCount,
            'started_at' => $this->localDateTime($request->matching_started_at, $timezone),
            'finished_at' => $this->localDateTime($request->resolveMatchingFinishedAt(), $timezone),
            'timezone' => $timezone,
            'criteria' => $this->criteriaFromMatch($latestMatch),
            'logo_url' => $request->logo_path ? route('public.file', ltrim($request->logo_path, '/')) : null,
        ];
    }

    private function matchPayload(CvMatch $match): array
    {
        return [
            'id' => $match->id,
            'score' => round((float) $match->score, 1),
            'summary' => $match->summary,
            'selected' => (bool) $match->selected,
            'ai_status' => $match->ai_analysis_status,
            'candidate' => [
                'id' => $match->cv?->id,
                'name' => $match->cv?->candidate_name ?: 'Candidat',
                'email' => $match->cv?->email,
                'phone' => $match->cv?->phone,
                'file' => $match->cv?->original_filename,
            ],
            'criteria' => $this->criteriaFromMatch($match),
        ];
    }

    private function criteriaFromMatch(?CvMatch $match): array
    {
        if (!$match || !is_array($match->score_breakdown)) {
            return [];
        }

        $items = [];

        foreach ($match->score_breakdown as $key => $value) {
            $score = null;
            $why = null;
            $evidence = null;

            if (is_numeric($value)) {
                $score = (float) $value;
            } elseif (is_array($value)) {
                $score = $value['score'] ?? $value['points'] ?? $value['value'] ?? null;
                $why = $value['why'] ?? $value['reason'] ?? $value['explanation'] ?? null;
                $evidence = $value['evidence'] ?? $value['extract'] ?? $value['quote'] ?? null;
            }

            if ($score === null || !is_numeric($score)) {
                continue;
            }

            $label = Str::of((string) $key)->replace(['_', '-'], ' ')->title()->toString();

            $items[] = [
                'name' => $label,
                'score' => round((float) $score, 1),
                'why' => $why ?: "Score calcule a partir des informations extraites du CV et des criteres de la demande.",
                'evidence' => $evidence ?: ($match->summary ?: 'Aucun extrait detaille disponible pour ce critere.'),
            ];
        }

        return $items;
    }
}
