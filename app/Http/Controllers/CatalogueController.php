<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function index(Request $request)
    {
        $query = Formation::query();

        // Search
        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        // Filters
        if ($request->filled('domain')) {
            $query->where('domain', $request->domain);
        }

        if ($request->filled('public')) {
            $query->where('public', $request->public);
        }

        if ($request->filled('format')) {
            $query->where('format', $request->format);
        }

        // ✅ FIX: paginate instead of get
        $formations = $query->latest()->paginate(9);

        // Dropdown filter values
        $domains = Formation::select('domain')->distinct()->pluck('domain');
        $publics = Formation::select('public')->distinct()->pluck('public');
        $formats = Formation::select('format')->distinct()->pluck('format');

        return view('pages.catalogue', compact(
            'formations',
            'domains',
            'publics',
            'formats'
        ));
    }
    
}
