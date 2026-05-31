<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeetingController extends Controller
{
    use ResolvesUserTimezone;

    public function index(Request $request)
    {
        $timezone = $this->userTimezone($request);

        $meetings = Meeting::query()
            ->with(['users:id,name,email,role', 'recruitmentRequest:id,reference,position_title'])
            ->forUser($request->user())
            ->orderByDesc('meeting_date')
            ->orderByDesc('start_time')
            ->paginate(20)
            ->through(fn (Meeting $meeting) => $this->payload($meeting, $timezone));

        return response()->json($meetings);
    }

    public function show(Request $request, Meeting $meeting)
    {
        abort_unless($request->user()->isAdmin() || $meeting->users()->whereKey($request->user()->id)->exists(), 403);

        $meeting->participants()
            ->where('user_id', $request->user()->id)
            ->whereNull('notification_read_at')
            ->update(['notification_read_at' => now()]);

        return response()->json($this->payload($meeting->load(['users:id,name,email,role', 'recruitmentRequest:id,reference,position_title']), $this->userTimezone($request)));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'online_link' => ['nullable', 'url', 'max:500'],
            'status' => ['nullable', Rule::in(array_keys(Meeting::statuses()))],
            'recruitment_request_id' => ['nullable', 'exists:recruitment_requests,id'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['integer', 'exists:users,id'],
        ]);

        $participants = $data['participants'];
        unset($data['participants']);

        $allowedParticipants = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])
            ->whereIn('id', $participants)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        abort_unless(count($allowedParticipants) === count(array_unique($participants)), 422, 'Les participants doivent etre des admins ou employes RHS.');

        $meeting = Meeting::create($data + [
            'created_by' => $request->user()->id,
            'status' => $data['status'] ?? Meeting::STATUS_SCHEDULED,
        ]);

        $meeting->users()->sync($allowedParticipants);

        return response()->json(
            $this->payload($meeting->load(['users:id,name,email,role', 'recruitmentRequest:id,reference,position_title']), $this->userTimezone($request)),
            201
        );
    }

    private function payload(Meeting $meeting, string $timezone): array
    {
        return [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'description' => $meeting->description,
            'meeting_date' => $this->localDate($meeting->meeting_date, $timezone),
            'date' => $this->localDate($meeting->meeting_date, $timezone),
            'timezone' => $timezone,
            'start_time' => $meeting->start_time,
            'end_time' => $meeting->end_time,
            'location' => $meeting->location,
            'online_link' => $meeting->online_link,
            'status' => Meeting::statuses()[$meeting->status] ?? $meeting->status,
            'raw_status' => $meeting->status,
            'request' => $meeting->recruitmentRequest ? [
                'id' => $meeting->recruitmentRequest->id,
                'reference' => $meeting->recruitmentRequest->reference,
                'title' => $meeting->recruitmentRequest->position_title,
            ] : null,
            'participants' => $meeting->relationLoaded('users')
                ? $meeting->users->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ])->values()
                : [],
        ];
    }
}
