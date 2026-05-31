<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\ExternalCv;
use App\Models\ExternalCvBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExternalCvController extends Controller
{
    use ResolvesUserTimezone;

    public function index(Request $request)
    {
        $this->authorizeExternalAccess($request->user());
        $timezone = $this->userTimezone($request);

        $batches = ExternalCvBatch::query()
            ->with('folder:id,name', 'creator:id,name')
            ->latest()
            ->paginate(20)
            ->through(fn (ExternalCvBatch $batch) => $this->batchPayload($batch, $timezone));

        return response()->json($batches);
    }

    public function show(Request $request, ExternalCvBatch $externalCvBatch)
    {
        $this->authorizeExternalAccess($request->user());
        $timezone = $this->userTimezone($request);

        $externalCvBatch->load('folder:id,name', 'creator:id,name');
        $files = $externalCvBatch->files()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ExternalCv $cv) => $this->filePayload($cv, $timezone))
            ->values();

        return response()->json([
            ...$this->batchPayload($externalCvBatch, $timezone),
            'notes' => $externalCvBatch->notes,
            'files' => $files,
        ]);
    }

    public function download(Request $request, ExternalCv $externalCv)
    {
        $this->authorizeExternalAccess($request->user());

        abort_unless(
            !empty($externalCv->stored_path) && Storage::disk('local')->exists($externalCv->stored_path),
            404,
            'External CV file not found.'
        );

        return Storage::disk('local')->download(
            $externalCv->stored_path,
            $externalCv->original_filename ?: ('external-cv-' . $externalCv->id),
            ['Content-Type' => $externalCv->mime_type ?: 'application/octet-stream']
        );
    }

    private function authorizeExternalAccess(User $user): void
    {
        abort_unless($user->isAdmin() || $user->hasAnyPermission(['external_cvs', 'external_cvs_manage']), 403);
    }

    private function batchPayload(ExternalCvBatch $batch, string $timezone): array
    {
        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'title' => $batch->name,
            'status' => ExternalCvBatch::availableStatuses()[$batch->status] ?? $batch->status,
            'raw_status' => $batch->status,
            'processing_status' => $batch->processing_status,
            'progress' => $batch->progressPercentage(),
            'total_files' => (int) $batch->total_files,
            'indexed_files' => (int) $batch->indexed_files,
            'failed_files' => (int) $batch->failed_files,
            'duplicate_files' => (int) $batch->duplicate_files,
            'folder' => $batch->folder?->name,
            'creator' => $batch->creator?->name,
            'created_at' => $this->localDate($batch->created_at, $timezone),
            'timezone' => $timezone,
        ];
    }

    private function filePayload(ExternalCv $cv, string $timezone): array
    {
        return [
            'id' => $cv->id,
            'candidate_name' => $cv->candidate_name ?: 'Candidat externe',
            'current_title' => $cv->current_title,
            'email' => $cv->email,
            'phone' => $cv->phone,
            'city' => $cv->city,
            'filename' => $cv->original_filename,
            'can_open' => !empty($cv->stored_path),
            'status' => $cv->status,
            'duplicate_score' => $cv->duplicate_score,
            'duplicate_reason' => $cv->duplicate_reason,
            'indexed_at' => $this->localDateTime($cv->indexed_at, $timezone),
            'timezone' => $timezone,
        ];
    }
}
