<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientRequestAlert;
use App\Models\ContactMessage;
use App\Models\Cv;
use App\Models\EmployeeInternalRequest;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeReport;
use App\Models\JobOffer;
use App\Models\JobApplication;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $payload = Cache::remember('admin.dashboard.shell.v1', now()->addSeconds(60), function () {
            return [
            'offersCount' => JobOffer::count(),
            'activeOffersCount' => JobOffer::query()->where('is_active', true)->count(),
            'appsUnread'  => JobApplication::where('is_read', false)->count(),
            'msgsUnread'  => ContactMessage::where('is_read', false)->count(),
            'pendingClientRequests' => RecruitmentRequest::query()
                ->whereNotNull('client_user_id')
                ->whereIn('request_status', [
                    RecruitmentRequest::STATUS_PENDING,
                    RecruitmentRequest::STATUS_UNDER_REVIEW,
                ])
                ->count(),
            'activeRecruitmentRequests' => RecruitmentRequest::query()
                ->whereNotNull('client_user_id')
                ->where(function ($query) {
                    $query->where('request_status', RecruitmentRequest::STATUS_MATCHING_IN_PROGRESS)
                        ->orWhere('matching_status', RecruitmentRequest::MATCHING_STATUS_PROCESSING)
                        ->orWhere('matching_job_status', RecruitmentRequest::JOB_STATUS_RUNNING);
                })
                ->count(),
            'totalUsers' => User::count(),
            'pendingUserApprovals' => User::where('status', User::STATUS_PENDING)->count(),
            'pendingClientAlerts' => Schema::hasTable('client_request_alerts')
                ? ClientRequestAlert::query()->where('status', ClientRequestAlert::STATUS_NEW)->count()
                : 0,
            'cvBankCount' => Cv::count(),
            'pendingEmployeeReports' => EmployeeReport::query()
                ->where('status', EmployeeReport::STATUS_PENDING)
                ->count(),
            'pendingLeaveRequests' => EmployeeLeaveRequest::query()
                ->where('status', EmployeeLeaveRequest::STATUS_PENDING)
                ->count(),
            'openInternalRequests' => EmployeeInternalRequest::query()
                ->whereIn('status', [
                    EmployeeInternalRequest::STATUS_PENDING,
                    EmployeeInternalRequest::STATUS_IN_PROGRESS,
                ])
                ->count(),
            'unassignedRecruitmentRequests' => RecruitmentRequest::query()
                ->whereNotNull('client_user_id')
                ->whereNull('assigned_employee_id')
                ->count(),
            'clientRequestStatusChart' => RecruitmentRequest::query()
                ->whereNotNull('client_user_id')
                ->selectRaw('request_status, COUNT(*) as total')
                ->groupBy('request_status')
                ->pluck('total', 'request_status')
                ->all(),
            'pipelineStageChart' => RecruitmentRequest::query()
                ->whereNotNull('client_user_id')
                ->selectRaw('pipeline_stage, COUNT(*) as total')
                ->groupBy('pipeline_stage')
                ->pluck('total', 'pipeline_stage')
                ->all(),
            'offerStatusChart' => [
                'active' => JobOffer::query()->where('is_active', true)->count(),
                'inactive' => JobOffer::query()->where('is_active', false)->count(),
            ],
            'employeeRequestTypeChart' => EmployeeInternalRequest::query()
                ->selectRaw('category, COUNT(*) as total')
                ->groupBy('category')
                ->pluck('total', 'category')
                ->all(),
            'userRoleChart' => User::query()
                ->selectRaw('role, COUNT(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role')
                ->all(),
            'assignedRequestsByEmployee' => $this->assignedRequestsByEmployee(),
            'employeePerformanceChart' => $this->employeePerformanceChart(),
            'cvsByOfferChart' => [],
            'cvsByFolderChart' => [],
            'cvSourceChart' => [],
            'cvHealthChart' => [],
            'requestsByClientChart' => [],
            'clientActivityChart' => [],
            'clientAlertChart' => [],
            'employeeTaskChart' => [],
            ];
        });

        return view('admin.dashboard', $payload);
    }

    public function charts(Request $request)
    {
        $chartKey = (string) $request->query('chart', '');

        if ($chartKey !== '') {
            return response()->json([
                $chartKey => Cache::remember(
                    'admin.dashboard.chart.' . $chartKey . '.v2',
                    now()->addSeconds(60),
                    fn () => $this->resolveChart($chartKey)
                ),
            ]);
        }

        return response()->json(Cache::remember('admin.dashboard.charts.v2', now()->addSeconds(60), fn () => [
            'cvsByOfferChart' => $this->cvsByOfferChart(),
            'cvsByFolderChart' => $this->cvsByFolderChart(),
            'cvSourceChart' => $this->cvSourceChart(),
            'cvHealthChart' => $this->cvHealthChart(),
            'requestsByClientChart' => $this->requestsByClientChart(),
            'clientActivityChart' => $this->clientActivityChart(),
            'clientAlertChart' => $this->clientAlertChart(),
            'employeeTaskChart' => $this->employeeTaskChart(),
        ]));
    }

    private function resolveChart(string $chartKey): array
    {
        return match ($chartKey) {
            'cvsByOfferChart' => $this->cvsByOfferChart(),
            'cvsByFolderChart' => $this->cvsByFolderChart(),
            'cvSourceChart' => $this->cvSourceChart(),
            'cvHealthChart' => $this->cvHealthChart(),
            'requestsByClientChart' => $this->requestsByClientChart(),
            'clientActivityChart' => $this->clientActivityChart(),
            'clientAlertChart' => $this->clientAlertChart(),
            'employeeTaskChart' => $this->employeeTaskChart(),
            default => [],
        };
    }

    private function assignedRequestsByEmployee(): array
    {
        if (!Schema::hasTable('recruitment_requests') || !Schema::hasTable('users')) {
            return [];
        }

        return RecruitmentRequest::query()
            ->leftJoin('users', 'users.id', '=', 'recruitment_requests.assigned_employee_id')
            ->whereNotNull('recruitment_requests.client_user_id')
            ->whereNotNull('recruitment_requests.assigned_employee_id')
            ->selectRaw("COALESCE(users.name, 'Employe') as label, COUNT(*) as total")
            ->groupBy('recruitment_requests.assigned_employee_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function employeePerformanceChart(): array
    {
        if (!Schema::hasTable('recruitment_requests') || !Schema::hasTable('users')) {
            return [];
        }

        return RecruitmentRequest::query()
            ->leftJoin('users', 'users.id', '=', 'recruitment_requests.assigned_employee_id')
            ->whereNotNull('recruitment_requests.client_user_id')
            ->where('recruitment_requests.assignment_status', RecruitmentRequest::ASSIGNMENT_STATUS_COMPLETED)
            ->whereNotNull('recruitment_requests.assigned_employee_id')
            ->selectRaw("COALESCE(users.name, 'Employe') as label, COUNT(*) as total")
            ->groupBy('recruitment_requests.assigned_employee_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function requestsByClientChart(): array
    {
        if (!Schema::hasTable('recruitment_requests') || !Schema::hasTable('users')) {
            return [];
        }

        return RecruitmentRequest::query()
            ->leftJoin('users', 'users.id', '=', 'recruitment_requests.client_user_id')
            ->whereNotNull('recruitment_requests.client_user_id')
            ->selectRaw("COALESCE(users.name, recruitment_requests.client_name, 'Client') as label, COUNT(*) as total")
            ->groupBy('recruitment_requests.client_user_id', 'users.name', 'recruitment_requests.client_name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function cvsByOfferChart(): array
    {
        if (
            !Schema::hasTable('cvs')
            || !Schema::hasTable('job_applications')
            || !Schema::hasTable('job_offers')
            || !Schema::hasColumn('cvs', 'source_type')
            || !Schema::hasColumn('cvs', 'source_id')
        ) {
            return [];
        }

        return Cv::query()
            ->leftJoin('job_applications', function ($join) {
                $join->on('job_applications.id', '=', 'cvs.source_id')
                    ->where('cvs.source_type', '=', 'application');
            })
            ->leftJoin('job_offers', 'job_offers.id', '=', 'job_applications.job_offer_id')
            ->selectRaw("
                CASE
                    WHEN cvs.source_type = 'application' AND job_applications.job_offer_id IS NULL THEN 'Candidatures spontanees'
                    WHEN cvs.source_type != 'application' OR cvs.source_type IS NULL THEN 'CV banque / import'
                    ELSE COALESCE(job_offers.title, 'Offre non renseignee')
                END as label,
                COUNT(cvs.id) as total
            ")
            ->groupBy('label')
            ->orderByDesc(DB::raw('COUNT(cvs.id)'))
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label ?: 'Offre',
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function cvSourceChart(): array
    {
        if (!Schema::hasTable('cvs') || !Schema::hasColumn('cvs', 'source_type')) {
            return [];
        }

        $labels = [
            'application' => 'Candidatures',
            'manual' => 'Ajout manuel',
            'external_db' => 'Base externe',
            'unknown' => 'Non renseigne',
        ];

        return Cv::query()
            ->selectRaw("COALESCE(source_type, 'unknown') as source_key, COUNT(*) as total")
            ->groupBy('source_key')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get()
            ->map(fn ($row) => [
                'label' => $labels[$row->source_key] ?? ucfirst((string) $row->source_key),
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function cvHealthChart(): array
    {
        if (!Schema::hasTable('cvs')) {
            return [];
        }

        $columns = collect(Schema::getColumnListing('cvs'))->flip();
        $hasColumn = fn (string $column) => $columns->has($column);
        $selects = [];
        $labels = [];

        if ($hasColumn('email')) {
            $selects[] = "SUM(CASE WHEN email IS NOT NULL AND email != '' THEN 1 ELSE 0 END) as email_total";
            $labels['email_total'] = 'Email renseigne';
        }

        if ($hasColumn('phone')) {
            $selects[] = "SUM(CASE WHEN phone IS NOT NULL AND phone != '' THEN 1 ELSE 0 END) as phone_total";
            $labels['phone_total'] = 'Telephone renseigne';
        }

        foreach (['encrypted_extracted_text', 'extracted_text', 'cv_text'] as $textColumn) {
            if ($hasColumn($textColumn)) {
                $selects[] = "SUM(CASE WHEN {$textColumn} IS NOT NULL AND {$textColumn} != '' THEN 1 ELSE 0 END) as text_total";
                $labels['text_total'] = 'Texte extrait';
                break;
            }
        }

        if ($hasColumn('cv_folder_id')) {
            $selects[] = "SUM(CASE WHEN cv_folder_id IS NOT NULL THEN 1 ELSE 0 END) as folder_total";
            $labels['folder_total'] = 'Dossier assigne';
        }

        if ($hasColumn('compression_verified_at')) {
            $selects[] = "SUM(CASE WHEN compression_verified_at IS NOT NULL THEN 1 ELSE 0 END) as compression_total";
            $labels['compression_total'] = 'Stockage optimise';
        }

        if ($hasColumn('archived_at')) {
            $selects[] = "SUM(CASE WHEN archived_at IS NULL THEN 1 ELSE 0 END) as active_total";
            $labels['active_total'] = 'CV actifs';
        }

        if (!$selects) {
            return [];
        }

        $row = Cv::query()->selectRaw(implode(', ', $selects))->first();

        return collect($labels)
            ->map(fn (string $label, string $key) => [
                'label' => $label,
                'total' => (int) ($row->{$key} ?? 0),
            ])
            ->filter(fn (array $row) => $row['total'] > 0)
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function cvsByFolderChart(): array
    {
        if (
            !Schema::hasTable('cvs')
            || !Schema::hasTable('cv_folders')
            || !Schema::hasColumn('cvs', 'cv_folder_id')
        ) {
            return [];
        }

        return Cv::query()
            ->leftJoin('cv_folders', 'cv_folders.id', '=', 'cvs.cv_folder_id')
            ->selectRaw("COALESCE(cv_folders.name, 'Sans dossier') as label, COUNT(cvs.id) as total")
            ->groupBy('label')
            ->orderByDesc(DB::raw('COUNT(cvs.id)'))
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label ?: 'Sans dossier',
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function clientActivityChart(): array
    {
        if (!Schema::hasTable('recruitment_requests')) {
            return [];
        }

        $requests = RecruitmentRequest::query()
            ->leftJoin('users', 'users.id', '=', 'recruitment_requests.client_user_id')
            ->whereNotNull('recruitment_requests.client_user_id')
            ->select('recruitment_requests.client_user_id')
            ->selectRaw("COALESCE(users.name, recruitment_requests.client_name, 'Client') as label")
            ->selectRaw('COUNT(*) as requests_total')
            ->groupBy('recruitment_requests.client_user_id', 'users.name', 'recruitment_requests.client_name')
            ->get()
            ->keyBy('client_user_id');

        $alerts = collect();

        if (Schema::hasTable('client_request_alerts')) {
            $alerts = ClientRequestAlert::query()
                ->select('client_user_id')
                ->selectRaw('COUNT(*) as alerts_total')
                ->groupBy('client_user_id')
                ->get()
                ->keyBy('client_user_id');
        }

        return $requests
            ->map(function ($request, $clientId) use ($alerts) {
                $alertsTotal = (int) ($alerts->get($clientId)?->alerts_total ?? 0);

                return [
                    'label' => $request->label ?: 'Client',
                    'value' => (int) $request->requests_total,
                    'secondary' => $alertsTotal,
                    'total' => (int) $request->requests_total + $alertsTotal,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function clientAlertChart(): array
    {
        if (!Schema::hasTable('client_request_alerts')) {
            return [];
        }

        return ClientRequestAlert::query()
            ->leftJoin('users', 'users.id', '=', 'client_request_alerts.client_user_id')
            ->selectRaw("COALESCE(users.name, 'Client') as label")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('client_request_alerts.client_user_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(8)
            ->get()
            ->map(fn ($alert) => [
                'label' => $alert->label ?: 'Client',
                'total' => (int) $alert->total,
            ])
            ->values()
            ->all();
    }

    private function employeeTaskChart(): array
    {
        $employees = User::query()
            ->select('id', 'name')
            ->whereIn('role', [User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])
            ->get()
            ->keyBy('id');

        $completedRequests = RecruitmentRequest::query()
            ->where('assignment_status', RecruitmentRequest::ASSIGNMENT_STATUS_COMPLETED)
            ->whereNotNull('assigned_employee_id')
            ->selectRaw('assigned_employee_id as user_id, COUNT(*) as total')
            ->groupBy('assigned_employee_id')
            ->pluck('total', 'user_id');

        $reports = EmployeeReport::query()
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $internalResponses = EmployeeInternalRequest::query()
            ->whereNotNull('responded_by')
            ->selectRaw('responded_by as user_id, COUNT(*) as total')
            ->groupBy('responded_by')
            ->pluck('total', 'user_id');

        $alertResponses = Schema::hasTable('client_request_alerts')
            ? ClientRequestAlert::query()
                ->whereNotNull('responded_by')
                ->selectRaw('responded_by as user_id, COUNT(*) as total')
                ->groupBy('responded_by')
                ->pluck('total', 'user_id')
            : collect();

        return $employees
            ->map(function (User $employee) use ($completedRequests, $reports, $internalResponses, $alertResponses) {
                $completed = (int) ($completedRequests[$employee->id] ?? 0);
                $reportCount = (int) ($reports[$employee->id] ?? 0);
                $responses = (int) ($internalResponses[$employee->id] ?? 0) + (int) ($alertResponses[$employee->id] ?? 0);

                return [
                    'label' => $employee->name,
                    'value' => $completed,
                    'secondary' => $reportCount,
                    'third' => $responses,
                    'total' => $completed + $reportCount + $responses,
                ];
            })
            ->filter(fn (array $row) => $row['total'] > 0)
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
