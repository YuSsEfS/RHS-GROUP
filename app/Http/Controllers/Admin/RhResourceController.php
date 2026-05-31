<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RhResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RhResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = RhResource::query()
            ->with('creator:id,name')
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim((string) $request->q);

                $query->where(function ($search) use ($q) {
                    $search->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('category', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.rh_resources.index', [
            'resources' => $resources,
            'categories' => RhResource::categories(),
            'roles' => $this->visibleRoles(),
        ]);
    }

    public function create()
    {
        return view('admin.rh_resources.create', [
            'resource' => new RhResource([
                'is_active' => true,
                'visibility_roles' => [User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR],
            ]),
            'categories' => RhResource::categories(),
            'roles' => $this->visibleRoles(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = auth()->id();
        $data['is_active'] = $request->boolean('is_active');
        $data['visibility_roles'] = $data['visibility_roles'] ?? null;

        $this->handleUpload($request, $data);

        $resource = RhResource::create($data);

        return redirect()
            ->route('admin.rh-resources.show', $resource)
            ->with('success', 'Ressource RH creee.');
    }

    public function show(RhResource $rhResource)
    {
        return view('admin.rh_resources.show', ['resource' => $rhResource->load('creator:id,name')]);
    }

    public function edit(RhResource $rhResource)
    {
        return view('admin.rh_resources.edit', [
            'resource' => $rhResource,
            'categories' => RhResource::categories(),
            'roles' => $this->visibleRoles(),
        ]);
    }

    public function update(Request $request, RhResource $rhResource)
    {
        $data = $this->validatedData($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['visibility_roles'] = $data['visibility_roles'] ?? null;

        $this->handleUpload($request, $data, $rhResource);
        $rhResource->update($data);

        return redirect()
            ->route('admin.rh-resources.show', $rhResource)
            ->with('success', 'Ressource RH mise a jour.');
    }

    public function destroy(RhResource $rhResource)
    {
        if ($rhResource->file_path && Storage::disk('local')->exists($rhResource->file_path)) {
            Storage::disk('local')->delete($rhResource->file_path);
        }

        $rhResource->delete();

        return redirect()
            ->route('admin.rh-resources.index')
            ->with('success', 'Ressource RH supprimee.');
    }

    public function download(RhResource $rhResource)
    {
        abort_unless($rhResource->file_path && Storage::disk('local')->exists($rhResource->file_path), 404);

        return Storage::disk('local')->download(
            $rhResource->file_path,
            $rhResource->original_filename ?: basename($rhResource->file_path)
        );
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', Rule::in(array_keys(RhResource::categories()))],
            'visibility_roles' => ['nullable', 'array'],
            'visibility_roles.*' => ['string', Rule::in(array_keys($this->visibleRoles()))],
            'is_active' => ['nullable', 'boolean'],
            'file' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,txt'],
        ]);
    }

    private function handleUpload(Request $request, array &$data, ?RhResource $resource = null): void
    {
        if (!$request->hasFile('file')) {
            return;
        }

        if ($resource?->file_path && Storage::disk('local')->exists($resource->file_path)) {
            Storage::disk('local')->delete($resource->file_path);
        }

        $file = $request->file('file');

        $data['file_path'] = $file->store('rh-resources', 'local');
        $data['original_filename'] = $file->getClientOriginalName();
        $data['mime_type'] = $file->getMimeType();
        $data['file_size'] = $file->getSize();
    }

    private function visibleRoles(): array
    {
        return [
            User::ROLE_EMPLOYEE => 'Employes',
            User::ROLE_SUPERVISOR => 'Superviseurs',
        ];
    }
}
