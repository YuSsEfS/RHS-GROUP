<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ExternalCv;
use App\Models\ExternalCvBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExternalCvController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $batches = ExternalCvBatch::query()
            ->with(['creator', 'folder'])
            ->withCount('cvs')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('employee.external_cvs.index', [
            'batches' => $batches,
            'q' => $q,
            'status' => $status,
            'canManageExternalCvs' => auth()->user()->hasPermission('external_cvs_manage'),
        ]);
    }

    public function show(ExternalCvBatch $externalCvBatch, Request $request)
    {
        $externalCvBatch->load(['creator', 'folder']);

        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $files = ExternalCv::query()
            ->where('batch_id', $externalCvBatch->id)
            ->with('cv')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('original_filename', 'like', "%{$q}%")
                        ->orWhere('candidate_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('current_title', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('employee.external_cvs.show', [
            'batch' => $externalCvBatch,
            'files' => $files,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function open(ExternalCv $externalCv)
    {
        abort_unless(auth()->user()?->hasAnyPermission(['external_cvs', 'external_cvs_manage']), 403);
        abort_unless(
            !empty($externalCv->stored_path) && Storage::disk('local')->exists($externalCv->stored_path),
            404,
            'Fichier introuvable.'
        );

        return response()->file(Storage::disk('local')->path($externalCv->stored_path), [
            'Content-Type' => $externalCv->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($externalCv->original_filename ?: ('external-cv-' . $externalCv->id)) . '"',
        ]);
    }
}
