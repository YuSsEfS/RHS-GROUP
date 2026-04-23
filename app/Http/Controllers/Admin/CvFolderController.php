<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CvFolderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $name = trim($validated['name']);
        $slug = \Illuminate\Support\Str::slug($name);

        $existing = CvFolder::where('slug', $slug)->first();

        if ($existing) {
            return back()->with('success', 'Le dossier existe déjà.');
        }

        CvFolder::create([
            'name' => $name,
            'description' => null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Dossier créé avec succès.');
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
            ->with('success', 'Dossier supprimé avec succès.');
    }
}