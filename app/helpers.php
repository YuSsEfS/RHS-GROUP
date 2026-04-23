<?php

use App\Models\ContentBlock;

if (!function_exists('cms')) {
    /**
     * Get a CMS text value by key: "page.section.field"
     */
    function cms(string $key, string $default = ''): string
    {
        $parts = explode('.', $key, 3);
        if (count($parts) !== 3) {
            return $default;
        }

        [$page, $section, $field] = $parts;

        $block = ContentBlock::query()
            ->where('page', $page)
            ->where('section', $section)
            ->where('field', $field)
            ->first();

        return $block?->value ?? $default;
    }
}

if (!function_exists('cms_img')) {
    /**
     * Get an image URL from CMS by key.
     * If the value is a storage path like "media/xxx.jpg" → returns asset('storage/media/xxx.jpg')
     * If empty → returns $default
     */
    function cms_img(string $key, string $default = ''): string
    {
        $value = cms($key, '');

        if (!$value) {
            return $default;
        }

        // If it's already a full URL, return it
        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        // If it's already an asset path (starts with /)
        if (str_starts_with($value, '/')) {
            return asset(ltrim($value, '/'));
        }

        // Default: stored in public disk (storage/app/public)
        return asset('storage/' . ltrim($value, '/'));
    }
}
