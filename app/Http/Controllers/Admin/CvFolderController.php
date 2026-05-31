<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CvFolderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $name = trim($validated['name']);
        $slug = Str::slug($name);

        $existing = CvFolder::where('slug', $slug)->first();

        if ($existing) {
            return back()->with('success', 'Le dossier existe deja.');
        }

        CvFolder::create([
            'name' => $name,
            'description' => null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Dossier cree avec succes.');
    }

    public function archive(CvFolder $cvFolder)
    {
        $archivedCount = Cv::query()
            ->where('cv_folder_id', $cvFolder->id)
            ->whereNull('archived_at')
            ->update([
                'archived_at' => now(),
                'archived_by' => auth()->id(),
                'archive_reason' => 'Archivage du dossier ' . $cvFolder->name,
                'is_active' => false,
            ]);

        return redirect()
            ->route('admin.cvs.archived')
            ->with('success', $archivedCount . ' CV du dossier ont ete archives.');
    }

    public function restore(CvFolder $cvFolder)
    {
        $restoredCount = Cv::query()
            ->where('cv_folder_id', $cvFolder->id)
            ->whereNotNull('archived_at')
            ->update([
                'archived_at' => null,
                'archived_by' => null,
                'archive_reason' => null,
                'is_active' => true,
            ]);

        return redirect()
            ->route('admin.cvs.index', ['folder' => $cvFolder->id])
            ->with('success', $restoredCount . ' CV du dossier ont ete restaures dans la CV Bank.');
    }

    public function destroy(Request $request, CvFolder $cvFolder)
    {
        $validated = $request->validate([
            'delete_mode' => ['required', 'in:folder_only,folder_and_files'],
        ]);

        DB::transaction(function () use ($validated, $cvFolder) {
            $cvs = Cv::where('cv_folder_id', $cvFolder->id)->get();

            if ($validated['delete_mode'] === 'folder_and_files') {
                foreach ($cvs as $cv) {
                    if (!empty($cv->encrypted_path) && Storage::disk('local')->exists($cv->encrypted_path)) {
                        Storage::disk('local')->delete($cv->encrypted_path);
                    }

                    $cv->delete();
                }
            } else {
                Cv::where('cv_folder_id', $cvFolder->id)->update([
                    'cv_folder_id' => null,
                ]);
            }

            $cvFolder->delete();
        });

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', 'Dossier supprime avec succes.');
    }
}
