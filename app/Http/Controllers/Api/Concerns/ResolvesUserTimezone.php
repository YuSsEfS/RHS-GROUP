<?php

namespace App\Http\Controllers\Api\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait ResolvesUserTimezone
{
    protected function userTimezone(Request $request): string
    {
        $timezone = trim((string) $request->header('X-User-Timezone', ''));
        $fallback = config('app.timezone', 'Africa/Casablanca');

        if ($timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
            return $timezone;
        }

        return in_array($fallback, timezone_identifiers_list(), true) ? $fallback : 'Africa/Casablanca';
    }

    protected function localNow(Request $request): Carbon
    {
        return now($this->userTimezone($request));
    }

    protected function localDate($value, string $timezone, string $format = 'd/m/Y'): ?string
    {
        if (blank($value)) {
            return null;
        }

        return $this->localCarbon($value, $timezone)->format($format);
    }

    protected function localDateTime($value, string $timezone, string $format = 'd/m/Y H:i'): ?string
    {
        if (blank($value)) {
            return null;
        }

        return $this->localCarbon($value, $timezone)->timezone($timezone)->format($format);
    }

    protected function isoDateTime($value, string $timezone): ?string
    {
        if (blank($value)) {
            return null;
        }

        return $this->localCarbon($value, $timezone)->timezone($timezone)->toIso8601String();
    }

    private function localCarbon($value, string $timezone): Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value->toDateTime());
        }

        return Carbon::parse($value, $timezone);
    }
}
