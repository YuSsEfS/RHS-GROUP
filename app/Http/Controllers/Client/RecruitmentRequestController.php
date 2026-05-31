<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use App\Models\RecruitmentRequest;
use Illuminate\Http\Request;

class RecruitmentRequestController extends Controller
{
    public function index()
    {
        return view('client.recruitment_requests.index', [
            'user' => auth()->user(),
            'requests' => RecruitmentRequest::query()
                ->where('client_user_id', auth()->id())
                ->withCount('clientAlerts')
                ->latest()
                ->paginate(12),
            'statuses' => RecruitmentRequest::availableStatuses(),
            'pipelineStages' => RecruitmentRequest::availablePipelineStages(),
        ]);
    }

    public function show(RecruitmentRequest $recruitmentRequest)
    {
        abort_unless((int) $recruitmentRequest->client_user_id === (int) auth()->id(), 403);

        return view('client.recruitment_requests.show', [
            'user' => auth()->user(),
            'requestItem' => $recruitmentRequest->load(['clientAlerts.responder'])->loadCount('clientAlerts'),
            'statuses' => RecruitmentRequest::availableStatuses(),
            'pipelineStages' => RecruitmentRequest::availablePipelineStages(),
            'alertStatuses' => ClientRequestAlert::availableStatuses(),
        ]);
    }

    public function create()
    {
        return view('client.recruitment_requests.create', [
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'position_title' => ['required', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:500'],
            'recruitment_reason' => ['nullable', 'string', 'max:255'],
            'candidate_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'age' => ['nullable', 'string', 'max:100'],
            'education' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'string', 'max:255'],
            'availability' => ['nullable', 'string', 'max:255'],
            'other_language' => ['nullable', 'string', 'max:255'],
            'budget_type' => ['nullable', 'string', 'max:255'],
            'monthly_salary' => ['nullable', 'string', 'max:255'],
            'contract_type' => ['nullable', 'string', 'max:255'],
            'planned_start_date' => ['nullable', 'date'],
            'missions' => ['nullable', 'string'],
            'personal_qualities' => ['nullable', 'string'],
            'specific_knowledge' => ['nullable', 'string'],
            'other_benefits' => ['nullable', 'string'],
            'lang_ar' => ['nullable'],
            'lang_fr' => ['nullable'],
            'lang_en' => ['nullable'],
            'lang_es' => ['nullable'],
        ]);

        $logoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('recruitment-requests', 'public')
            : null;

        $createData = [
            'client_user_id' => auth()->id(),
            'client_name' => auth()->user()->name,
            'logo_path' => $logoPath,
            'request_date' => now()->toDateString(),
            'reference' => $validated['reference'] ?? null,
            'position_title' => $validated['position_title'],
            'work_location' => $validated['work_location'] ?? null,
            'work_locations' => $validated['work_location'] ?? null,
            'recruitment_reason' => $validated['recruitment_reason'] ?? null,
            'candidate_count' => $validated['candidate_count'] ?? null,
            'age' => $validated['age'] ?? null,
            'education' => $validated['education'] ?? null,
            'experience_years' => $validated['experience_years'] ?? null,
            'availability' => $validated['availability'] ?? null,
            'other_language' => $validated['other_language'] ?? null,
            'budget_type' => $validated['budget_type'] ?? null,
            'monthly_salary' => $validated['monthly_salary'] ?? null,
            'contract_type' => $validated['contract_type'] ?? null,
            'planned_start_date' => $validated['planned_start_date'] ?? null,
            'missions' => $validated['missions'] ?? null,
            'personal_qualities' => $validated['personal_qualities'] ?? null,
            'specific_knowledge' => $validated['specific_knowledge'] ?? null,
            'other_benefits' => $validated['other_benefits'] ?? null,
            'lang_ar' => $request->boolean('lang_ar'),
            'lang_fr' => $request->boolean('lang_fr'),
            'lang_en' => $request->boolean('lang_en'),
            'lang_es' => $request->boolean('lang_es'),
            'request_status' => RecruitmentRequest::STATUS_PENDING,
            'pipeline_stage' => RecruitmentRequest::PIPELINE_STAGE_NEW,
            'admin_notes' => null,
        ];

        if (!\Illuminate\Support\Facades\Schema::hasColumn('recruitment_requests', 'candidate_count')) {
            unset($createData['candidate_count']);
        }

        RecruitmentRequest::create($createData);

        return redirect()
            ->route('client.recruitment-requests.index')
            ->with('success', 'Votre demande de recrutement a ete envoyee avec succes.');
    }
}
