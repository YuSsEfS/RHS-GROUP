<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use Illuminate\Http\Request; // ✅ IMPORTANT


class JobOfferController extends Controller
{
   public function index(Request $request)
{
    $q        = trim((string) $request->query('q', ''));
    $location = trim((string) $request->query('location', ''));
    $contract = trim((string) $request->query('contract', ''));
    $sector   = trim((string) $request->query('sector', ''));
    $sort     = (string) $request->query('sort', 'new'); // new|old

    $offers = JobOffer::query()
        ->where('is_active', true)
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%")
                   ->orWhere('company', 'like', "%{$q}%");
            });
        })
        ->when($location !== '', fn ($query) => $query->where('location', $location))
        ->when($contract !== '', fn ($query) => $query->where('contract_type', $contract))
        ->when($sector !== '', fn ($query) => $query->where('sector', $sector))
        ->when($sort === 'old', fn ($query) => $query->orderBy('published_at', 'asc'))
        ->when($sort !== 'old', fn ($query) => $query->orderBy('published_at', 'desc'))
        ->paginate(10)
        ->appends($request->query());

    // dropdown options
    $locations = JobOffer::query()
        ->where('is_active', true)
        ->whereNotNull('location')->where('location','!=','')
        ->distinct()->orderBy('location')->pluck('location');

    $contracts = JobOffer::query()
        ->where('is_active', true)
        ->whereNotNull('contract_type')->where('contract_type','!=','')
        ->distinct()->orderBy('contract_type')->pluck('contract_type');

    $sectors = JobOffer::query()
        ->where('is_active', true)
        ->whereNotNull('sector')->where('sector','!=','')
        ->distinct()->orderBy('sector')->pluck('sector');

    return view('pages.jobs', compact('offers', 'locations', 'contracts', 'sectors'));
}

    public function show(string $slug)
    {
        $offer = JobOffer::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('pages.job-detail', compact('offer'));
    }
    public function suggest(Request $request)
{
    $q = trim((string) $request->query('q', ''));

    if ($q === '' || mb_strlen($q) < 2) {
        return response()->json([]);
    }

    $items = JobOffer::query()
        ->select('title', 'slug', 'location', 'contract_type')
        ->where('is_active', true)
        ->where('title', 'like', "%{$q}%")
        ->orderByRaw("CASE WHEN title LIKE ? THEN 0 ELSE 1 END, title ASC", ["{$q}%"])
        ->limit(6)
        ->get()
        ->map(function ($o) {
            $metaParts = array_filter([$o->location ?: null, $o->contract_type ?: null]);

            return [
                'title' => $o->title,
                'slug'  => $o->slug,
                'meta'  => implode(' • ', $metaParts),
            ];
        });

    return response()->json($items);
}

}
