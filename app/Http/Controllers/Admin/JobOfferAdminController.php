<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobOfferAdminController extends Controller
{
    public function index(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $status   = (string) $request->query('status', 'all'); // all|active|inactive
        $contract = trim((string) $request->query('contract', ''));

        $offers = JobOffer::query()
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->when($contract !== '', fn ($query) => $query->where('contract_type', $contract))
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        $contracts = JobOffer::query()
            ->whereNotNull('contract_type')
            ->where('contract_type', '!=', '')
            ->distinct()
            ->orderBy('contract_type')
            ->pluck('contract_type');

        return view('admin.offers.index', compact('offers', 'contracts'));
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = JobOffer::query()
            ->select('id', 'title', 'location', 'contract_type')
            ->where('title', 'like', "%{$q}%")
            ->orderByRaw("CASE WHEN title LIKE ? THEN 0 ELSE 1 END, title ASC", ["{$q}%"])
            ->limit(3)
            ->get()
            ->map(function ($o) {
                $metaParts = array_filter([$o->location ?: null, $o->contract_type ?: null]);

                return [
                    'id'    => $o->id,
                    'title' => $o->title,
                    'meta'  => implode(' • ', $metaParts),
                ];
            });

        return response()->json($items);
    }

    public function create()
    {
        return view('admin.offers.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $data['is_active'] = $request->boolean('is_active', false);

        $data['published_at'] = $request->filled('published_at')
            ? $request->input('published_at')
            : null;

        // ✅ Validate image ONLY if uploaded
        if ($request->hasFile('hero_image')) {
            $request->validate([
                'hero_image' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            $data['hero_image'] = $request->file('hero_image')->store('offers/hero', 'public');
        }

        JobOffer::create($data);

        return redirect()
            ->route('admin.offers.index')
            ->with('success', 'Offre créée avec succès.');
    }

    public function edit(JobOffer $offer)
    {
        return view('admin.offers.edit', compact('offer'));
    }

    public function update(Request $request, JobOffer $offer)
    {
        $data = $this->validateData($request);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $data['is_active'] = $request->boolean('is_active', false);

        $data['published_at'] = $request->filled('published_at')
            ? $request->input('published_at')
            : null;

        // ✅ Validate image ONLY if uploaded
        if ($request->hasFile('hero_image')) {
            $request->validate([
                'hero_image' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);

            if (!empty($offer->hero_image)) {
                Storage::disk('public')->delete($offer->hero_image);
            }

            $data['hero_image'] = $request->file('hero_image')->store('offers/hero', 'public');
        }

        $offer->update($data);

        return redirect()
            ->route('admin.offers.index')
            ->with('success', 'Offre mise à jour avec succès.');
    }

    public function publish(JobOffer $offer)
    {
        $offer->is_active = true;

        if (empty($offer->published_at)) {
            $offer->published_at = now();
        }

        $offer->save();

        return back()->with('success', 'Offre publiée avec succès.');
    }

    public function destroy(JobOffer $offer)
    {
        if (!empty($offer->hero_image)) {
            Storage::disk('public')->delete($offer->hero_image);
        }

        $offer->delete();

        return redirect()
            ->route('admin.offers.index')
            ->with('success', 'Offre supprimée.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255'],
            'company'       => ['nullable', 'string', 'max:255'],
            'location'      => ['nullable', 'string', 'max:255'],
            'contract_type' => ['nullable', 'string', 'max:255'],
            'sector'        => ['nullable', 'string', 'max:255'],
            'excerpt'       => ['nullable', 'string'],
            'description'   => ['nullable', 'string'],
            'missions'      => ['nullable', 'string'],
            'requirements'  => ['nullable', 'string'],
            'published_at'  => ['nullable', 'date'],
        ]);
    }
}
