<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use App\Services\CvStorageOptimizationService;
use Illuminate\Http\Request;
use ZipArchive;

class CvDownloadController extends Controller
{
    public function downloadSelected(
        Request $request,
        RecruitmentRequest $recruitmentRequest,
        CvStorageOptimizationService $storageOptimization
    )
    {
        if ($request->isMethod('post')) {
            $autoSelectCount = max(0, min((int) $request->input('auto_select_count', 0), 5000));
            $visibleIds = collect($request->input('visible_matches', []))
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();
            $selectedIds = collect($request->input('selected_matches', []))
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();

            if ($autoSelectCount > 0) {
                $selectedIds = $recruitmentRequest->matches()
                    ->orderByDesc('score')
                    ->limit($autoSelectCount)
                    ->pluck('id');

                $recruitmentRequest->matches()->update(['selected' => false]);

                if ($selectedIds->isNotEmpty()) {
                    $recruitmentRequest->matches()
                        ->whereIn('id', $selectedIds->all())
                        ->update(['selected' => true]);
                }
            } elseif ($visibleIds->isNotEmpty()) {
                $recruitmentRequest->matches()
                    ->whereIn('id', $visibleIds->all())
                    ->update(['selected' => false]);

                if ($selectedIds->isNotEmpty()) {
                    $recruitmentRequest->matches()
                        ->whereIn('id', $selectedIds->all())
                        ->update(['selected' => true]);
                }
            }
        }

        $matches = $recruitmentRequest
            ->matches()
            ->where('selected', true)
            ->with('cv')
            ->get();

        if ($matches->isEmpty()) {
            return back()->with('error', 'Aucun CV sélectionné.');
        }

        $tempFolder = storage_path('app/temp');

        // Ensure temp directory exists
        if (!file_exists($tempFolder)) {
            mkdir($tempFolder, 0777, true);
        }

        $zipFilename = 'selected-cvs-request-' .
            $recruitmentRequest->id .
            '-' .
            now()->format('Ymd_His') .
            '.zip';

        $zipPath = $tempFolder . '/' . $zipFilename;

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Impossible de créer le fichier ZIP.');
        }

        $addedFiles = 0;

        foreach ($matches as $match) {

            if (!$match->cv) {
                continue;
            }

            $cv = $match->cv;

            $binary = $storageOptimization->readBinary($cv);

            if ($binary === null) {
                continue;
            }

            $extension = pathinfo((string) ($cv->original_filename ?: 'cv'), PATHINFO_EXTENSION);
            $safeFilename =
                ($cv->candidate_name ?? 'cv') .
                '-' .
                $cv->id .
                ($extension ? '.' . $extension : '');

            $zip->addFromString($safeFilename, $binary);

            $addedFiles++;
        }

        $zip->close();

        // Important safety check
        if ($addedFiles === 0 || !file_exists($zipPath)) {
            return back()->with('error', 'Aucun fichier valide trouvé pour téléchargement.');
        }

        return response()
            ->download($zipPath)
            ->deleteFileAfterSend(true);
    }
}
