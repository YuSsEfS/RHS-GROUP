<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use Illuminate\Http\Request;

class FormationController extends Controller
{
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $formations = Formation::query()
            ->select('id', 'title', 'domain')
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('domain', 'like', "%{$q}%");
            })
            ->orderBy('title')
            ->limit(8)
            ->get()
            ->map(fn (Formation $formation) => [
                'id' => $formation->id,
                'title' => $formation->title,
                'domain' => $formation->domain,
            ]);

        return response()->json($formations);
    }

    public function publish(Request $request, Formation $formation)
    {
        $formation->update([
            'featured' => $request->boolean('featured', !$formation->featured),
        ]);

        return back()->with('success', 'La mise en avant de la formation a ete mise a jour.');
    }

    /**
     * Display a listing of formations
     */
    public function index(Request $request)
    {
        $q = $request->q;

        $formations = Formation::when($q, function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                      ->orWhere('domain', 'like', "%{$q}%");
            })
            ->latest()
            ->get();

        return view('admin.formations.index', compact('formations', 'q'));
    }

    /**
     * Show the form for creating a new formation
     */
    public function create()
    {
        return view('admin.formations.create');
    }

    /**
     * Store a newly created formation
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'domain'       => 'nullable|string|max:255',
            'public'       => 'nullable|string|max:255',
            'format'       => 'nullable|string|max:255',
            'duration'     => 'nullable|string|max:255',
            'audience'     => 'nullable|string|max:255',
            'format_label' => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'program'      => 'nullable|string',
            'featured'     => 'sometimes|boolean',
        ]);

        // ✅ Always set boolean properly
        $data['featured'] = $request->has('featured');

        Formation::create($data);

        return redirect()
            ->route('admin.formations.index')
            ->with('success', 'Formation créée avec succès');
    }

    /**
     * Show the form for editing the specified formation
     */
    public function edit(Formation $formation)
    {
        return view('admin.formations.edit', compact('formation'));
    }

    /**
     * Update the specified formation
     */
    public function update(Request $request, Formation $formation)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'domain'       => 'nullable|string|max:255',
            'public'       => 'nullable|string|max:255',
            'format'       => 'nullable|string|max:255',
            'duration'     => 'nullable|string|max:255',
            'audience'     => 'nullable|string|max:255',
            'format_label' => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'program'      => 'nullable|string',
            'featured'     => 'sometimes|boolean',
        ]);

        // ✅ Always set boolean properly
        $data['featured'] = $request->has('featured');

        $formation->update($data);

        return redirect()
            ->route('admin.formations.index')
            ->with('success', 'Formation mise à jour avec succès');
    }

    /**
     * Remove the specified formation
     */
    public function destroy(Formation $formation)
    {
        $formation->delete();

        return redirect()
            ->route('admin.formations.index')
            ->with('success', 'Formation supprimée avec succès');
    }
}
