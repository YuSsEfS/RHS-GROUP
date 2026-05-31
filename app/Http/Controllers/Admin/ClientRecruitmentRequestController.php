<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClientRecruitmentRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', 'all'));
        $assignment = trim((string) $request->query('assignment', 'all'));
        $q = trim((string) $request->query('q', ''));

        $query = RecruitmentRequest::query()
            ->with(['clientUser', 'assignedEmployee'])
            ->withCount(['matches', 'clientAlerts'])
            ->whereNotNull('client_user_id')
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('request_status', $status))
            ->when($assignment !== '' && $assignment !== 'all', function ($query) use ($assignment) {
                match ($assignment) {
                    'assigned' => $query->whereNotNull('assigned_employee_id'),
                    'assigned_unseen' => $query
                        ->whereNotNull('assigned_employee_id')
                        ->whereNull('assignment_seen_at'),
                    'assigned_in_progress' => $query
                        ->where('assignment_status', RecruitmentRequest::ASSIGNMENT_STATUS_IN_PROGRESS),
                    'assigned_completed' => $query
                        ->where('assignment_status', RecruitmentRequest::ASSIGNMENT_STATUS_COMPLETED),
                    'unassigned' => $query->whereNull('assigned_employee_id'),
                    default => null,
                };
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('client_name', 'like', "%{$q}%")
                        ->orWhere('reference', 'like', "%{$q}%")
                        ->orWhere('position_title', 'like', "%{$q}%");
                });
            });

        $requests = (clone $query)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        RecruitmentRequest::query()
            ->whereNotNull('client_user_id')
            ->whereNull('admin_seen_at')
            ->update(['admin_seen_at' => now()]);

        return view('admin.client_recruitment_requests.index', [
            'requests' => $requests,
            'statuses' => RecruitmentRequest::availableStatuses(),
            'status' => $status,
            'assignment' => $assignment,
            'q' => $q,
        ]);
    }

    public function edit(RecruitmentRequest $clientRecruitmentRequest)
    {
        abort_unless($clientRecruitmentRequest->client_user_id, 404);

        if (is_null($clientRecruitmentRequest->admin_seen_at)) {
            $clientRecruitmentRequest->forceFill([
                'admin_seen_at' => now(),
            ])->save();
        }

        return view('admin.client_recruitment_requests.edit', [
            'recruitmentRequest' => $clientRecruitmentRequest->load(['clientUser', 'assignedEmployee'])->loadCount(['matches', 'clientAlerts']),
            'statuses' => RecruitmentRequest::availableStatuses(),
            'assignmentStatuses' => RecruitmentRequest::availableAssignmentStatuses(),
            'pipelineStages' => RecruitmentRequest::availablePipelineStages(),
            'jobStatuses' => RecruitmentRequest::availableJobStatuses(),
            'employees' => User::query()
                ->whereIn('role', [User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])
                ->where('status', User::STATUS_APPROVED)
                ->orderBy('name')
                ->get(['id', 'name', 'permissions']),
        ]);
    }

    public function update(Request $request, RecruitmentRequest $clientRecruitmentRequest)
    {
        abort_unless($clientRecruitmentRequest->client_user_id, 404);

        $validated = $request->validate([
            'request_status' => ['required', Rule::in(array_keys(RecruitmentRequest::availableStatuses()))],
            'assigned_employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'assignment_status' => ['nullable', Rule::in(array_keys(RecruitmentRequest::availableAssignmentStatuses()))],
            'pipeline_stage' => ['nullable', Rule::in(array_keys(RecruitmentRequest::availablePipelineStages()))],
            'admin_notes' => ['nullable', 'string'],
            'employee_notes' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $payload = $validated;
        unset($payload['logo']);
        $employeeChanged = (int) ($clientRecruitmentRequest->assigned_employee_id ?? 0) !== (int) ($payload['assigned_employee_id'] ?? 0);

        if ($request->hasFile('logo') && Schema::hasColumn('recruitment_requests', 'logo_path')) {
            if ($clientRecruitmentRequest->logo_path && Storage::disk('public')->exists($clientRecruitmentRequest->logo_path)) {
                Storage::disk('public')->delete($clientRecruitmentRequest->logo_path);
            }

            $payload['logo_path'] = $request->file('logo')->store('recruitment-requests', 'public');
        }

        if (empty($payload['assigned_employee_id'])) {
            $payload['assigned_employee_id'] = null;
            $payload['assignment_status'] = null;
            $payload['assignment_seen_at'] = null;
        } else {
            $payload['assignment_status'] = $payload['assignment_status'] ?: RecruitmentRequest::ASSIGNMENT_STATUS_ASSIGNED;

            if ($employeeChanged) {
                $payload['assignment_seen_at'] = null;
            }
        }

        $payload['pipeline_stage'] = $payload['pipeline_stage']
            ?: ($clientRecruitmentRequest->pipeline_stage ?: RecruitmentRequest::PIPELINE_STAGE_NEW);

        $clientRecruitmentRequest->update($payload);

        return redirect()
            ->route('admin.client-recruitment-requests.index')
            ->with('success', 'Demande client mise à jour avec succès.');
    }
}
