<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all'); // all|read|unread
        $offer  = (string) $request->query('offer', 'all');  // all|spontaneous|{id}

        $applications = JobApplication::query()
            ->with(['offer'])

            // ✅ search by candidate name OR email OR city
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('full_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%");
                });
            })

            // ✅ read/unread
            ->when(in_array($status, ['read', 'unread'], true), function ($query) use ($status) {
                $query->where('is_read', $status === 'read');
            })

            // ✅ offer filter
            ->when($offer === 'spontaneous', function ($query) {
                $query->whereNull('job_offer_id');
            })
            ->when($offer !== 'all' && $offer !== 'spontaneous', function ($query) use ($offer) {
                $query->where('job_offer_id', (int) $offer);
            })

            ->latest()
->get();


        $offers = JobOffer::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        return view('admin.applications.index', compact('applications', 'offers'));
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $items = JobApplication::query()
            ->select('id', 'full_name', 'email', 'city', 'job_offer_id', 'is_read')
            ->with(['offer:id,title'])
            ->where(function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            })
            ->orderByRaw(
                "CASE WHEN full_name LIKE ? THEN 0 ELSE 1 END, full_name ASC",
                ["{$q}%"]
            )
            ->limit(3)
            ->get()
            ->map(function ($a) {
                $offerTitle = $a->offer?->title ?? 'Spontanée';
                $status = $a->is_read ? 'Lu' : 'Non lu';
                $city = $a->city ? (' • ' . $a->city) : '';

                return [
                    'id'    => $a->id,
                    'title' => $a->full_name,
                    'meta'  => $a->email . $city . ' • ' . $offerTitle . ' • ' . $status,
                ];
            });

        return response()->json($items);
    }

    public function show($id)
    {
        $application = JobApplication::with(['offer'])->findOrFail($id);

        if (!$application->is_read) {
            $application->is_read = true;
            $application->save();
        }

        return view('admin.applications.show', compact('application'));
    }

    public function cv(JobApplication $application)
    {
        abort_unless($application->cv_path, 404);

        return Storage::disk('public')->response($application->cv_path);
    }

    public function letter(JobApplication $application)
    {
        abort_unless($application->letter_path, 404);

        return Storage::disk('public')->response($application->letter_path);
    }
}
