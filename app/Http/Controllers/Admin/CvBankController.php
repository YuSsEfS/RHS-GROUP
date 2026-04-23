<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\CvFolder;
use Illuminate\Http\Request;

class CvBankController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $source = trim((string) $request->query('source', 'all'));
        $folder = trim((string) $request->query('folder', 'all'));

        $cvs = Cv::query()
            ->with('folder')

            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('candidate_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('current_title', 'like', "%{$q}%")
                        ->orWhere('original_filename', 'like', "%{$q}%");
                });
            })

            ->when($source !== '' && $source !== 'all', function ($query) use ($source) {
                $query->where('source_type', $source);
            })

            ->when($folder !== '' && $folder !== 'all', function ($query) use ($folder) {
                $query->where('cv_folder_id', (int) $folder);
            })

            ->latest()
            ->paginate(20)
            ->withQueryString();

        $folders = CvFolder::query()
            ->orderBy('name')
            ->get();

        return view('admin.cv-bank.index', compact('cvs', 'folders', 'q', 'source', 'folder'));
    }


    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'cv_ids' => ['required', 'array', 'min:1'],
            'cv_ids.*' => ['integer', 'exists:cvs,id'],
        ]);

        Cv::whereIn('id', $validated['cv_ids'])->delete();

        return redirect()
            ->route('admin.cvs.index')
            ->with('success', count($validated['cv_ids']) . ' CV supprimé(s) avec succès.');
    }
}