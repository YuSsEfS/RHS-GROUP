<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\RhResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RhResourceController extends Controller
{
    use ResolvesUserTimezone;

    public function index(Request $request)
    {
        $timezone = $this->userTimezone($request);

        $resources = RhResource::query()
            ->visibleFor($request->user())
            ->with('creator:id,name,email,profile_photo_path')
            ->latest()
            ->paginate(20)
            ->through(fn (RhResource $resource) => $this->payload($resource, $timezone));

        return response()->json($resources);
    }

    public function show(Request $request, RhResource $rhResource)
    {
        abort_unless(
            RhResource::query()->whereKey($rhResource->id)->visibleFor($request->user())->exists(),
            403
        );

        $rhResource->load('creator:id,name,email,profile_photo_path');

        return response()->json($this->payload($rhResource, $this->userTimezone($request)));
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

    private function payload(RhResource $resource, string $timezone): array
    {
        $categories = RhResource::categories();

        return [
            'id' => $resource->id,
            'title' => $resource->title,
            'description' => $resource->description,
            'category' => $resource->category,
            'category_label' => $categories[$resource->category] ?? $resource->category,
            'file_path' => $resource->file_path,
            'original_filename' => $resource->original_filename,
            'mime_type' => $resource->mime_type,
            'file_size' => $resource->file_size,
            'visibility_roles' => $resource->visibility_roles,
            'is_active' => $resource->is_active,
            'created_by' => $resource->created_by,
            'creator' => $resource->creator ? [
                'id' => $resource->creator->id,
                'name' => $resource->creator->name,
                'email' => $resource->creator->email,
                'profile_photo_url' => $resource->creator->profile_photo_url,
            ] : null,
            'download_url' => route('api.rh-resources.download', $resource),
            'created_at' => $this->localDateTime($resource->created_at, $timezone),
            'created_at_iso' => $this->isoDateTime($resource->created_at, $timezone),
            'updated_at' => $this->localDateTime($resource->updated_at, $timezone),
            'updated_at_iso' => $this->isoDateTime($resource->updated_at, $timezone),
            'timezone' => $timezone,
        ];
    }
}
