<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\PhpExecutableFinder;

class MatchingWorkerLauncher
{
    public function start(string $queue): void
    {
        $php = (new PhpExecutableFinder())->find(false) ?: PHP_BINARY;
        $artisan = base_path('artisan');
        $queue = preg_replace('/[^A-Za-z0-9_-]/', '', $queue) ?: 'recruitment';
        $command = '"' . $php . '" "' . $artisan . '" queue:work database --queue=' . $queue . ' --once --timeout=900 --tries=1 --sleep=1';

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                pclose(popen('start "" /B ' . $command, 'r'));
            } else {
                exec($command . ' > /dev/null 2>&1 &');
            }
        } catch (\Throwable $e) {
            Log::warning('Unable to start dedicated matching worker', [
                'queue' => $queue,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
