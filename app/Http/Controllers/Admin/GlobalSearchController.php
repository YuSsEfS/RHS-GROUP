<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminEmployeeConversation;
use App\Models\Cv;
use App\Models\ExternalCvBatch;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\Meeting;
use App\Models\RecruitmentRequest;
use App\Models\RhResource;
use App\Models\User;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return view('admin.search.index', [
            'q' => $q,
            'results' => $q === '' ? [] : $this->search($q, 5, false),
        ]);
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        return response()->json($this->search($q, 5, true));
    }

    private function search(string $q, int $limit, bool $flat): array
    {
        $groups = [
            'Utilisateurs' => User::query()
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn (User $user) => [
                    'label' => $user->name . ' - ' . $user->email,
                    'url' => route('admin.users.edit', $user),
                ])->all(),
            'Demandes clients' => RecruitmentRequest::query()
                ->whereNotNull('client_user_id')
                ->where(function ($query) use ($q) {
                    $query->where('client_name', 'like', "%{$q}%")
                        ->orWhere('position_title', 'like', "%{$q}%")
                        ->orWhere('reference', 'like', "%{$q}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn (RecruitmentRequest $request) => [
                    'label' => ($request->client_name ?: 'Client') . ' - ' . $request->position_title,
                    'url' => route('admin.client-recruitment-requests.edit', $request),
                ])->all(),
            'Matching' => RecruitmentRequest::query()
                ->where(function ($query) use ($q) {
                    $query->where('position_title', 'like', "%{$q}%")
                        ->orWhere('reference', 'like', "%{$q}%");
                })
                ->where(function ($query) {
                    $query->whereNotNull('matching_status')
                        ->orWhereHas('matches');
                })
                ->limit($limit)
                ->get()
                ->map(fn (RecruitmentRequest $request) => [
                    'label' => ($request->position_title ?: 'Matching') . ' - ' . ($request->reference ?: 'Sans reference'),
                    'url' => route('admin.recruitment_requests.results', $request),
                ])->all(),
            'CV Bank' => Cv::query()
                ->where(function ($query) use ($q) {
                    $query->where('candidate_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('original_filename', 'like', "%{$q}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn (Cv $cv) => [
                    'label' => ($cv->candidate_name ?: 'CV') . ' - ' . ($cv->original_filename ?: 'Sans nom de fichier'),
                    'url' => route('admin.cvs.index', ['q' => $cv->candidate_name ?: $cv->original_filename]),
                ])->all(),
            'Offres' => JobOffer::query()
                ->where('title', 'like', "%{$q}%")
                ->limit($limit)
                ->get()
                ->map(fn (JobOffer $offer) => [
                    'label' => $offer->title,
                    'url' => route('admin.offers.edit', $offer),
                ])->all(),
            'Candidatures' => JobApplication::query()
                ->where(function ($query) use ($q) {
                    $query->where('full_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('position', 'like', "%{$q}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn (JobApplication $application) => [
                    'label' => ($application->full_name ?: 'Candidature') . ' - ' . ($application->position ?: 'Sans poste'),
                    'url' => route('admin.applications.show', $application),
                ])->all(),
            'Messages employes' => class_exists(AdminEmployeeConversation::class)
                ? AdminEmployeeConversation::query()
                    ->with(['participantOneUser', 'participantTwoUser'])
                    ->forParticipant(auth()->user())
                    ->where(function ($query) use ($q) {
                        $query->where('subject', 'like', "%{$q}%")
                            ->orWhereHas('participantOneUser', fn ($userQuery) => $userQuery->where('name', 'like', "%{$q}%"))
                            ->orWhereHas('participantTwoUser', fn ($userQuery) => $userQuery->where('name', 'like', "%{$q}%"));
                    })
                    ->limit($limit)
                    ->get()
                    ->map(fn (AdminEmployeeConversation $conversation) => [
                        'label' => ($conversation->subject ?: 'Conversation') . ' - ' . ($conversation->otherParticipantFor(auth()->user())?->name ?: 'Utilisateur'),
                        'url' => route('admin.conversations.show', $conversation),
                    ])->all()
                : [],
            'Base externe' => ExternalCvBatch::query()
                ->where('name', 'like', "%{$q}%")
                ->limit($limit)
                ->get()
                ->map(fn (ExternalCvBatch $batch) => [
                    'label' => $batch->name,
                    'url' => route('admin.external-cvs.show', $batch),
                ])->all(),
            'Reunions' => Meeting::query()
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('location', 'like', "%{$q}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn (Meeting $meeting) => [
                    'label' => $meeting->title . ' - ' . $meeting->meeting_date?->format('d/m/Y'),
                    'url' => route('admin.meetings.show', $meeting),
                ])->all(),
            'Ressources RH' => RhResource::query()
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('category', 'like', "%{$q}%");
                })
                ->limit($limit)
                ->get()
                ->map(fn (RhResource $resource) => [
                    'label' => $resource->title,
                    'url' => route('admin.rh-resources.show', $resource),
                ])->all(),
        ];

        if ($flat) {
            return collect($groups)
                ->flatMap(function (array $items, string $group) {
                    return collect($items)->map(fn (array $item) => [
                        'group' => $group,
                        ...$item,
                    ]);
                })
                ->take($limit)
                ->values()
                ->all();
        }

        return array_filter($groups);
    }
}
