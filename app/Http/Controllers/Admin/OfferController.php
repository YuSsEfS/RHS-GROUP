<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all'); // all | active | inactive
        $contract = trim((string) $request->query('contract', '')); // optional filter

        $offers = Offer::query()
            // ✅ SEARCH BY TITLE ONLY (as requested)
            ->when($q !== '', function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%");
            })
            // ✅ STATUS FILTER
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            // ✅ CONTRACT FILTER (optional)
            ->when($contract !== '', function ($query) use ($contract) {
                $query->where('contract_type', $contract);
            })
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        // for filter dropdown options
        $contracts = Offer::query()
            ->select('contract_type')
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

        $items = Offer::query()
            ->select('id', 'title', 'location', 'contract_type')
            // ✅ SUGGESTIONS BY TITLE ONLY
            ->where('title', 'like', "%{$q}%")
            ->orderByRaw("CASE WHEN title LIKE ? THEN 0 ELSE 1 END, title ASC", ["{$q}%"])
            ->limit(3)
            ->get()
            ->map(function ($o) {
                $metaParts = array_filter([
                    $o->location ?: null,
                    $o->contract_type ?: null,
                ]);
                return [
                    'id' => $o->id,
                    'title' => $o->title,
                    'meta' => implode(' • ', $metaParts),
                ];
            });

        return response()->json($items);
    }
}
