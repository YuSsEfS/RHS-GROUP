<?php

namespace App\Http\Controllers;

use App\Models\Formation;

class FormationPublicController extends Controller
{
public function show($id)
{
    $formation = Formation::findOrFail($id);

    // Render the existing detail view
    return view('pages.formation-detail', compact('formation'));
}

}
