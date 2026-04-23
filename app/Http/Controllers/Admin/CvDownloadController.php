<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentRequest;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CvDownloadController extends Controller
{
    public function downloadSelected(RecruitmentRequest $recruitmentRequest)
    {
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

            if (!$cv->encrypted_path) {
                continue;
            }

            if (!Storage::disk('local')->exists($cv->encrypted_path)) {
                continue;
            }

            $fullPath = Storage::disk('local')->path($cv->encrypted_path);

            $safeFilename =
                ($cv->candidate_name ?? 'cv') .
                '-' .
                $cv->id .
                '.' .
                pathinfo($fullPath, PATHINFO_EXTENSION);

            $zip->addFile($fullPath, $safeFilename);

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