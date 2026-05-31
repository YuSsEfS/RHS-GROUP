<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Http\Request;

class RecruitmentRequestController extends Controller
{
    use ResolvesUserTimezone;

    public function index(Request $request)
    {
        $user = $request->user();

        $requests = RecruitmentRequest::query()
            ->select([
                'id',
                'reference',
                'client_name',
                'position_title',
                'request_date',
                'request_status',
                'pipeline_stage',
                'assignment_status',
                'assigned_employee_id',
                'client_user_id',
                'candidate_count',
                'work_location',
                'logo_path',
                'created_at',
                'updated_at',
            ])
            ->when($user->hasRole(User::ROLE_CLIENT), fn ($query) => $query->where('client_user_id', $user->id))
            ->when($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR]), function ($query) use ($user) {
                $query->where('assigned_employee_id', $user->id);
            })
            ->latest()
            ->paginate(20)
            ->through(fn (RecruitmentRequest $item) => $this->listPayload($item, $request));

        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'position_title' => ['required', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'candidate_count' => ['required', 'integer', 'min:1'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'missions' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        $recruitmentRequest = RecruitmentRequest::create([
            'client_user_id' => $user->hasRole(User::ROLE_CLIENT) ? $user->id : null,
            'client_name' => $data['client_name'] ?? $user->name,
            'request_date' => $this->localNow($request)->toDateString(),
            'position_title' => $data['position_title'],
            'work_location' => $data['work_location'] ?? null,
            'candidate_count' => $data['candidate_count'],
            'experience_years' => $data['experience_years'] ?? null,
            'education' => $data['education'] ?? null,
            'missions' => $data['missions'] ?? null,
            'request_status' => RecruitmentRequest::STATUS_PENDING,
            'pipeline_stage' => RecruitmentRequest::PIPELINE_STAGE_NEW,
        ]);

        return response()->json($this->listPayload($recruitmentRequest, $request), 201);
    }

    public function show(Request $request, RecruitmentRequest $recruitmentRequest)
    {
        $user = $request->user();

        if ($user->hasRole(User::ROLE_CLIENT)) {
            abort_unless((int) $recruitmentRequest->client_user_id === (int) $user->id, 403);

            return response()->json($this->clientPayload($recruitmentRequest, $request));
        }

        if ($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])) {
            abort_unless((int) $recruitmentRequest->assigned_employee_id === (int) $user->id, 403);
        }

        return response()->json([
            ...$this->listPayload($recruitmentRequest->load(['assignedEmployee:id,name,email', 'clientUser:id,name,email']), $request),
            'client_name' => $recruitmentRequest->client_name,
            'position_title' => $recruitmentRequest->position_title,
            'request_status' => $recruitmentRequest->request_status,
            'assignment_status' => $recruitmentRequest->assignment_status,
            'assigned_employee' => $recruitmentRequest->assignedEmployee,
            'client_user' => $recruitmentRequest->clientUser,
            'admin_notes' => $recruitmentRequest->admin_notes,
            'employee_notes' => $recruitmentRequest->employee_notes,
        ]);
    }

    private function listPayload(RecruitmentRequest $item, Request $httpRequest): array
    {
        $timezone = $this->userTimezone($httpRequest);

        return [
            'id' => $item->id,
            'reference' => $item->reference,
            'title' => $item->position_title,
            'position' => $item->position_title,
            'client' => $item->client_name,
            'company' => $item->client_name,
            'location' => $item->work_location,
            'count' => $item->candidate_count,
            'candidates_count' => $item->candidate_count,
            'status' => RecruitmentRequest::availableStatuses()[$item->request_status] ?? $item->request_status,
            'pipeline_stage' => $item->pipeline_stage,
            'request_date' => $this->localDate($item->request_date, $timezone),
            'request_date_iso' => optional($item->request_date)->toDateString(),
            'logo_url' => $item->logo_path ? route('public.file', ltrim($item->logo_path, '/')) : null,
            'created_at' => $this->localDateTime($item->created_at, $timezone),
            'created_at_iso' => $this->isoDateTime($item->created_at, $timezone),
            'updated_at' => $this->localDateTime($item->updated_at, $timezone),
            'updated_at_iso' => $this->isoDateTime($item->updated_at, $timezone),
            'timezone' => $timezone,
        ];
    }

    private function clientPayload(RecruitmentRequest $request, Request $httpRequest): array
    {
        $timezone = $this->userTimezone($httpRequest);

        return [
            'id' => $request->id,
            'reference' => $request->reference,
            'client_name' => $request->client_name,
            'position_title' => $request->position_title,
            'request_date' => $this->localDate($request->request_date, $timezone),
            'request_date_iso' => optional($request->request_date)->toDateString(),
            'request_status' => $request->request_status,
            'pipeline_stage' => $request->pipeline_stage,
            'admin_notes' => $request->admin_notes,
            'logo_url' => $request->logo_path ? route('public.file', ltrim($request->logo_path, '/')) : null,
            'created_at' => $this->localDateTime($request->created_at, $timezone),
            'created_at_iso' => $this->isoDateTime($request->created_at, $timezone),
            'updated_at' => $this->localDateTime($request->updated_at, $timezone),
            'updated_at_iso' => $this->isoDateTime($request->updated_at, $timezone),
            'timezone' => $timezone,
        ];
    }
}
