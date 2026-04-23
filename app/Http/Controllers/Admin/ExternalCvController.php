<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use App\Models\ExternalCv;
use App\Models\ExternalCvBatch;
use App\Services\ExternalCvIndexingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExternalCvController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $batches = ExternalCvBatch::query()
            ->withCount('cvs')
            ->with(['creator', 'folder'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%");
                });
            })
            ->when($status !== '' && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        return view('admin.external-cvs.index', compact('batches', 'q', 'status'));
    }

    public function create()
    {
        $folders = CvFolder::query()->orderBy('name')->get();

        return view('admin.external-cvs.create', compact('folders'));
    }

    public function store(Request $request)
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '1024M');

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'cv_folder_id' => ['nullable', 'exists:cv_folders,id'],
            'cv_files' => ['required', 'array', 'min:1'],
            'cv_files.*' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:51200'],

            'batch_id' => ['nullable', 'integer', 'exists:external_cv_batches,id'],
            'chunk_index' => ['nullable', 'integer', 'min:0'],
            'total_chunks' => ['nullable', 'integer', 'min:1'],
            'total_files' => ['nullable', 'integer', 'min:1'],
        ]);

        $isAjax = $request->expectsJson() || $request->ajax();

        $batch = null;

        if (!empty($validated['batch_id'])) {
            $batch = ExternalCvBatch::query()->findOrFail($validated['batch_id']);
            $folderId = $batch->cv_folder_id;
        } else {
            $name = trim((string) ($validated['name'] ?? ''));

            if ($name === '') {
                $name = 'Lot CV ' . now()->format('Y-m-d H-i-s');
            }

            $folderId = $validated['cv_folder_id'] ?? null;

            if (!$folderId) {
                $folderName = $name ?: ('Lot CV ' . now()->format('Y-m-d H-i-s'));
                $slug = Str::slug($folderName);

                $folder = CvFolder::query()->firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $folderName,
                        'description' => 'Dossier créé automatiquement depuis un lot externe.',
                        'created_by' => auth()->id(),
                    ]
                );

                $folderId = $folder->id;
            }

            $batch = ExternalCvBatch::create([
                'name' => $name,
                'notes' => $validated['notes'] ?? null,
                'cv_folder_id' => $folderId,
                'total_files' => (int) ($validated['total_files'] ?? count($request->file('cv_files', []))),
                'indexed_files' => 0,
                'failed_files' => 0,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
        }

        $storedCount = 0;
        $failedCount = 0;

        foreach ($request->file('cv_files', []) as $file) {
            try {
                $storedPath = $file->store('private/external-cvs/' . $batch->id, 'local');

                $hash = null;

                try {
                    $hash = hash_file('sha256', $file->getRealPath());
                } catch (\Throwable $e) {
                    $hash = null;
                }

                ExternalCv::create([
                    'batch_id' => $batch->id,
                    'cv_id' => null,
                    'candidate_name' => null,
                    'email' => null,
                    'phone' => null,
                    'city' => null,
                    'current_title' => null,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'stored_path' => $storedPath,
                    'file_hash' => $hash,
                    'extracted_text' => null,
                    'structured_profile' => null,
                    'status' => 'pending',
                    'error_message' => null,
                    'indexed_at' => null,
                ]);

                $storedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                report($e);
            }
        }

        $realTotal = ExternalCv::query()
            ->where('batch_id', $batch->id)
            ->count();

        $batch->update([
            'total_files' => max((int) $batch->total_files, (int) ($validated['total_files'] ?? $realTotal), $realTotal),
            'failed_files' => (int) $batch->failed_files + $failedCount,
        ]);

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'batch_id' => $batch->id,
                'stored' => $storedCount,
                'failed' => $failedCount,
                'current_total' => $realTotal,
                'redirect_url' => route('admin.external-cvs.show', $batch),
                'message' => $storedCount . ' CV ajouté(s) au lot.',
            ]);
        }

        return redirect()
            ->route('admin.external-cvs.show', $batch)
            ->with('success', 'Lot importé avec succès. Dossier CV Bank affecté automatiquement.');
    }

    public function show(ExternalCvBatch $externalCvBatch, Request $request)
    {
        $externalCvBatch->load(['creator', 'folder']);
        $externalCvBatch->refresh();

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
            ->when($status !== '' && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        return view('admin.external-cvs.show', [
            'batch' => $externalCvBatch,
            'files' => $files,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function indexBatch(
        Request $request,
        ExternalCvBatch $externalCvBatch,
        ExternalCvIndexingService $indexingService
    ) {
        try {
            $forceReindex = (bool) $request->boolean('force_reindex');

            if ($forceReindex) {
                $indexingService->reindexBatch($externalCvBatch);
                $message = 'Réindexation locale du lot terminée avec succès.';
            } else {
                $indexingService->indexBatch($externalCvBatch);
                $message = 'Indexation locale du lot terminée avec succès.';
            }

            $externalCvBatch->refresh();

            return redirect()
                ->route('admin.external-cvs.show', $externalCvBatch)
                ->with('success', $message);
        } catch (\Throwable $e) {
            $externalCvBatch->update([
                'status' => 'failed',
            ]);

            return redirect()
                ->route('admin.external-cvs.show', $externalCvBatch)
                ->with('error', 'Erreur lors de l’indexation du lot : ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, ExternalCvBatch $externalCvBatch)
    {
        $validated = $request->validate([
            'delete_mode' => ['required', 'in:batch_only,batch_and_indexed_cvs'],
        ]);

        DB::transaction(function () use ($validated, $externalCvBatch) {
            $files = ExternalCv::query()
                ->where('batch_id', $externalCvBatch->id)
                ->get();

            if ($validated['delete_mode'] === 'batch_and_indexed_cvs') {
                $cvIds = $files->pluck('cv_id')->filter()->unique()->values();

                if ($cvIds->isNotEmpty()) {
                    $cvs = Cv::query()->whereIn('id', $cvIds)->get();

                    foreach ($cvs as $cv) {
                        if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
                            Storage::disk('local')->delete($cv->encrypted_path);
                        }

                        $cv->delete();
                    }
                }
            } else {
                $cvIds = $files->pluck('cv_id')->filter()->unique()->values();

                if ($cvIds->isNotEmpty()) {
                    $cvs = Cv::query()->whereIn('id', $cvIds)->get();

                    foreach ($cvs as $cv) {
                        $payload = [];

                        if (Schema::hasColumn('cvs', 'source_type')) {
                            $payload['source_type'] = null;
                        }

                        if (Schema::hasColumn('cvs', 'source_id')) {
                            $payload['source_id'] = null;
                        }

                        if (!empty($payload)) {
                            $cv->update($payload);
                        }
                    }
                }
            }

            foreach ($files as $file) {
                if (!empty($file->stored_path) && Storage::disk('local')->exists($file->stored_path)) {
                    Storage::disk('local')->delete($file->stored_path);
                }

                $file->delete();
            }

            $externalCvBatch->delete();
        });

        return redirect()
            ->route('admin.external-cvs.index')
            ->with('success', 'Dossier d’indexation supprimé avec succès.');
    }

    public function open(ExternalCv $externalCv)
    {
        abort_unless(
            !empty($externalCv->stored_path) && Storage::disk('local')->exists($externalCv->stored_path),
            404,
            'Fichier introuvable.'
        );

        $fullPath = Storage::disk('local')->path($externalCv->stored_path);
        $filename = $externalCv->original_filename ?: ('external-cv-' . $externalCv->id);
        $mime = $externalCv->mime_type ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }
}