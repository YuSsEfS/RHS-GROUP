<?php

namespace App\Support;

use App\Models\ContentBlock;

class Cms
{
    public static function get(string $page, string $section, string $field, string $default = ''): string
    {
        return ContentBlock::where(compact('page','section','field'))->value('value') ?? $default;
    }
}
