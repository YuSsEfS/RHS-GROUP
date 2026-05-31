<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class MatchingProgressService
{
    public function progressKey(int $requestId): string
    {
        return 'matching:progress:' . $requestId;
    }

    public function cancelKey(int $requestId): string
    {
        return 'matching:cancel:' . $requestId;
    }

    public function start(int $requestId, int $total): void
    {
        Cache::put($this->progressKey($requestId), [
            'processed' => 0,
            'total' => max(0, $total),
            'matches' => 0,
            'started_at' => now()->timestamp,
            'updated_at' => now()->timestamp,
        ], now()->addHours(6));

        Cache::forget($this->cancelKey($requestId));
    }

    public function tick(int $requestId, int $processed, int $matches, ?int $total = null): void
    {
        $payload = Cache::get($this->progressKey($requestId), []);

        $payload['processed'] = max(0, $processed);
        $payload['matches'] = max(0, $matches);
        $payload['total'] = max(0, $total ?? (int) ($payload['total'] ?? 0));
        $payload['updated_at'] = now()->timestamp;

        Cache::put($this->progressKey($requestId), $payload, now()->addHours(6));
    }

    public function finish(int $requestId, int $matches): void
    {
        $payload = Cache::get($this->progressKey($requestId), []);
        $total = max((int) ($payload['total'] ?? 0), (int) ($payload['processed'] ?? 0), $matches);

        $payload['processed'] = $total;
        $payload['total'] = $total;
        $payload['matches'] = $matches;
        $payload['updated_at'] = now()->timestamp;

        Cache::put($this->progressKey($requestId), $payload, now()->addHours(6));
    }

    public function payload(int $requestId): array
    {
        return Cache::get($this->progressKey($requestId), []);
    }

    public function cancel(int $requestId): void
    {
        Cache::put($this->cancelKey($requestId), true, now()->addHours(6));
    }

    public function isCancelled(int $requestId): bool
    {
        return (bool) Cache::get($this->cancelKey($requestId), false);
    }
}
