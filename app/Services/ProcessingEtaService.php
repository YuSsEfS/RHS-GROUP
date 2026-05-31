<?php

namespace App\Services;

use Carbon\CarbonInterface;

class ProcessingEtaService
{
    public function payload(
        int $processed,
        int $total,
        ?CarbonInterface $startedAt,
        ?string $status = null,
        ?int $recentProcessed = null,
        ?int $recentWindowSeconds = null,
        bool $preferRecent = false
    ): array {
        $processed = max(0, $processed);
        $total = max(0, $total);
        $processed = $total > 0 ? min($processed, $total) : $processed;
        $progress = $total > 0 ? (int) round(($processed / $total) * 100) : 0;
        $remaining = max(0, $total - $processed);
        $elapsedSeconds = $startedAt ? max(0, $startedAt->diffInSeconds(now(), true)) : null;
        $estimatedSeconds = $this->estimateSeconds($processed, $remaining, $elapsedSeconds, $status);
        $etaBasis = 'global';
        $throughputPerMinute = null;

        if ($recentProcessed !== null && $recentWindowSeconds !== null) {
            $recentProcessed = max(0, $recentProcessed);
            $recentWindowSeconds = max(1, $recentWindowSeconds);

            if ($remaining <= 0) {
                $estimatedSeconds = 0;
                $etaBasis = 'complete';
            } elseif ($recentProcessed > 0) {
                $throughputPerSecond = $recentProcessed / $recentWindowSeconds;
                $throughputPerMinute = round($throughputPerSecond * 60, 2);
                $estimatedSeconds = (int) ceil($remaining / max(0.0001, $throughputPerSecond));
                $etaBasis = 'recent';
            } elseif ($preferRecent && $estimatedSeconds === null) {
                $estimatedSeconds = null;
                $etaBasis = 'waiting_for_progress';
            }
        }

        return [
            'processed_items' => $processed,
            'total_items' => $total,
            'remaining_items' => $remaining,
            'progress_percentage' => $progress,
            'elapsed_seconds' => $elapsedSeconds,
            'estimated_seconds_remaining' => $estimatedSeconds,
            'estimated_time_remaining' => $this->formatDuration($estimatedSeconds),
            'eta_basis' => $etaBasis,
            'recent_processed_items' => $recentProcessed,
            'recent_window_seconds' => $recentWindowSeconds,
            'throughput_per_minute' => $throughputPerMinute,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    public function estimateSeconds(
        int $processed,
        int $remaining,
        ?int $elapsedSeconds,
        ?string $status = null
    ): ?int {
        if ($remaining <= 0 || in_array($status, ['completed', 'termine', 'done'], true)) {
            return 0;
        }

        if ($processed <= 0 || !$elapsedSeconds || $elapsedSeconds <= 0) {
            return null;
        }

        $secondsPerItem = $elapsedSeconds / max(1, $processed);

        return (int) ceil($secondsPerItem * $remaining);
    }

    public function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return 'Calcul en cours';
        }

        if ($seconds <= 0) {
            return 'Termine';
        }

        if ($seconds < 60) {
            return '< 1 min';
        }

        $minutes = (int) ceil($seconds / 60);

        if ($minutes < 60) {
            return '~ ' . $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return '~ ' . $hours . ' h';
        }

        return '~ ' . $hours . ' h ' . $remainingMinutes . ' min';
    }
}
