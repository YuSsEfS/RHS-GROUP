<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeetingController extends Controller
{
    public function index(Request $request)
    {
        $meetings = Meeting::query()
            ->with(['users:id,name,email,role', 'recruitmentRequest:id,reference,position_title'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim((string) $request->q);

                $query->where(function ($search) use ($q) {
                    $search->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('location', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('meeting_date')
            ->orderByDesc('start_time')
            ->paginate(25)
            ->withQueryString();

        return view('admin.meetings.index', [
            'meetings' => $meetings,
            'statuses' => Meeting::statuses(),
        ]);
    }

    public function create()
    {
        return view('admin.meetings.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $participants = $data['participants'] ?? [];
        unset($data['participants']);

        $meeting = Meeting::create($data + [
            'created_by' => auth()->id(),
            'status' => $data['status'] ?? Meeting::STATUS_SCHEDULED,
        ]);

        $meeting->users()->sync($participants);

        return redirect()
            ->route('admin.meetings.show', $meeting)
            ->with('success', 'Reunion planifiee avec succes.');
    }

    public function show(Meeting $meeting)
    {
        $meeting->participants()
            ->where('user_id', auth()->id())
            ->whereNull('notification_read_at')
            ->update(['notification_read_at' => now()]);

        $meeting->load(['users:id,name,email,role', 'creator:id,name', 'recruitmentRequest:id,reference,position_title']);

        return view('admin.meetings.show', compact('meeting'));
    }

    public function edit(Meeting $meeting)
    {
        $meeting->load('users:id');

        return view('admin.meetings.edit', $this->formData() + [
            'meeting' => $meeting,
            'selectedParticipants' => $meeting->users->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Meeting $meeting)
    {
        $data = $this->validatedData($request);
        $participants = $data['participants'] ?? [];
        unset($data['participants']);

        $meeting->update($data);
        $meeting->users()->sync($participants);

        return redirect()
            ->route('admin.meetings.show', $meeting)
            ->with('success', 'Reunion mise a jour.');
    }

    public function destroy(Meeting $meeting)
    {
        $meeting->delete();

        return redirect()
            ->route('admin.meetings.index')
            ->with('success', 'Reunion supprimee.');
    }

    private function formData(): array
    {
        return [
            'participants' => User::query()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])
                ->where('status', User::STATUS_APPROVED)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role']),
            'recruitmentRequests' => RecruitmentRequest::query()
                ->latest()
                ->limit(100)
                ->get(['id', 'reference', 'position_title']),
            'statuses' => Meeting::statuses(),
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
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
    }
}
