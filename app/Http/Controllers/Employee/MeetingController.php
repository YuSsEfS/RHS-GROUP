<?php

namespace App\Http\Controllers\Employee;

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
            ->forUser($request->user())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderByDesc('meeting_date')
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString();

        return view('employee.meetings.index', [
            'meetings' => $meetings,
            'statuses' => Meeting::statuses(),
        ]);
    }

    public function show(Request $request, Meeting $meeting)
    {
        abort_unless($request->user()->isAdmin() || $meeting->users()->whereKey($request->user()->id)->exists(), 403);

        $meeting->participants()
            ->where('user_id', $request->user()->id)
            ->whereNull('notification_read_at')
            ->update(['notification_read_at' => now()]);

        $meeting->load(['users:id,name,email,role', 'recruitmentRequest:id,reference,position_title']);

        return view('employee.meetings.show', compact('meeting'));
    }

    public function create()
    {
        return view('employee.meetings.create', $this->formData() + [
            'meeting' => null,
            'selectedParticipants' => [auth()->id()],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $participants = array_values(array_unique(array_merge($data['participants'] ?? [], [$request->user()->id])));
        unset($data['participants']);

        $meeting = Meeting::create($data + [
            'created_by' => $request->user()->id,
            'status' => $data['status'] ?? Meeting::STATUS_SCHEDULED,
        ]);

        $meeting->users()->sync($participants);

        return redirect()
            ->route('employee.meetings.show', $meeting)
            ->with('success', 'Reunion creee avec succes.');
    }

    public function edit(Request $request, Meeting $meeting)
    {
        abort_unless((int) $meeting->created_by === (int) $request->user()->id, 403);

        $meeting->load('users:id');

        return view('employee.meetings.edit', $this->formData() + [
            'meeting' => $meeting,
            'selectedParticipants' => $meeting->users->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Meeting $meeting)
    {
        abort_unless((int) $meeting->created_by === (int) $request->user()->id, 403);

        $data = $this->validatedData($request);
        $participants = array_values(array_unique(array_merge($data['participants'] ?? [], [$request->user()->id])));
        unset($data['participants']);

        $meeting->update($data);
        $meeting->users()->sync($participants);

        return redirect()
            ->route('employee.meetings.show', $meeting)
            ->with('success', 'Reunion mise a jour.');
    }

    public function destroy(Request $request, Meeting $meeting)
    {
        abort_unless((int) $meeting->created_by === (int) $request->user()->id, 403);

        $meeting->delete();

        return redirect()
            ->route('employee.meetings.index')
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
                ->where('assigned_employee_id', auth()->id())
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
