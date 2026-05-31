<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\RhResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RhResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = RhResource::query()
            ->visibleFor($request->user())
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim((string) $request->q);

                $query->where(function ($search) use ($q) {
                    $search->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('category', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('employee.rh_resources.index', [
            'resources' => $resources,
            'categories' => RhResource::categories(),
        ]);
    }

    public function show(Request $request, RhResource $rhResource)
    {
        abort_unless(
            RhResource::query()->whereKey($rhResource->id)->visibleFor($request->user())->exists(),
            403
        );

        return view('employee.rh_resources.show', ['resource' => $rhResource]);
    }

    public function download(Request $request, RhResource $rhResource)
    {
        abort_unless(
            RhResource::query()->whereKey($rhResource->id)->visibleFor($request->user())->exists(),
            403
        );

        abort_unless($rhResource->file_path && Storage::disk('local')->exists($rhResource->file_path), 404);

        return Storage::disk('local')->download(
            $rhResource->file_path,
            $rhResource->original_filename ?: basename($rhResource->file_path)
        );
    }
}
