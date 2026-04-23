<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    /**
     * Classic content editor (your current page)
     */
    public function index()
    {
        $blocks = ContentBlock::orderBy('page')
            ->orderBy('section')
            ->orderBy('sort')
            ->get();

        return view('admin.content.index', compact('blocks'));
    }

    /**
     * Classic save (your current save)
     */
    public function save(Request $request)
    {
        // inputs in format: blocks[id] => value
        $values = $request->input('blocks', []);
        foreach ($values as $id => $value) {
            ContentBlock::where('id', $id)->update(['value' => $value]);
        }

        // image uploads in format: images[id]
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $id => $file) {
                if (!$file) continue;

                $path = $file->store('media', 'public'); // storage/app/public/media
                ContentBlock::where('id', $id)->update(['value' => $path]);
            }
        }

        return back()->with('success', 'Contenu mis à jour.');
    }

    /* ==========================================================
     | BUILDER (locked Elementor style)
     |==========================================================*/

    /**
     * Show the visual builder page (admin)
     */
    public function builder()
    {
        // We'll open home page by default in preview
        // builder=1 allows the front layout to inject cms-inline.js
        $startUrl = route('home') . '?builder=1';

        return view('admin.content.builder', compact('startUrl'));
    }

    /**
     * Provide content blocks to the builder as key => value
     * Key format: page.section.field
     */
    public function builderData(Request $request)
    {
        $blocks = ContentBlock::query()->get()->mapWithKeys(function ($b) {
            $key = $b->page . '.' . $b->section . '.' . $b->field;
            return [$key => $b->value];
        });

        return response()->json([
            'ok' => true,
            'blocks' => $blocks,
        ]);
    }

    /**
     * Save TEXT changes from builder
     * Request: { items: [ {key: "home.hero.title", value:"..."}, ... ] }
     */
    public function builderSave(Request $request)
    {
        $items = $request->input('items', []);

        foreach ($items as $item) {
            $key = $item['key'] ?? null;
            $value = $item['value'] ?? null;

            if (!$key) continue;

            // key format: page.section.field
            $parts = explode('.', $key, 3);
            if (count($parts) !== 3) continue;

            [$page, $section, $field] = $parts;

            // Update existing block (or create if missing)
            ContentBlock::updateOrCreate(
                ['page' => $page, 'section' => $section, 'field' => $field],
                ['value' => $value, 'sort' => 0]
            );
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Upload IMAGE changes from builder
     * Request: multipart { key: "home.hero.image", image: file }
     */
    public function builderUpload(Request $request)
    {
        $request->validate([
            'key' => ['required', 'string'],
            'image' => ['required', 'image', 'max:4096'], // 4MB
        ]);

        $key = $request->input('key');
        $parts = explode('.', $key, 3);

        if (count($parts) !== 3) {
            return response()->json(['ok' => false, 'error' => 'Invalid key'], 422);
        }

        [$page, $section, $field] = $parts;

        // store new image
        $path = $request->file('image')->store('media', 'public');

        // Update existing block (or create if missing)
        ContentBlock::updateOrCreate(
            ['page' => $page, 'section' => $section, 'field' => $field],
            ['value' => $path, 'sort' => 0]
        );

        return response()->json([
            'ok' => true,
            'path' => $path,
            'url' => asset('storage/' . $path),
        ]);
    }
}
