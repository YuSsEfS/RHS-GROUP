<?php

namespace App\Console\Commands;

use App\Models\MobilePushToken;
use App\Services\ExpoPushNotificationService;
use Illuminate\Console\Command;

class TestMobileNotificationCommand extends Command
{
    protected $signature = 'mobile:notifications:test {user_id? : Optional user id to target}';

    protected $description = 'Send a test push notification to registered mobile devices.';

    public function handle(ExpoPushNotificationService $push): int
    {
        $tokens = MobilePushToken::query()
            ->when($this->argument('user_id'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->latest('last_registered_at')
            ->get();

        if ($tokens->isEmpty()) {
            $this->warn('No registered mobile push tokens found.');
            return self::FAILURE;
        }

        $push->send($tokens->map(fn (MobilePushToken $token) => [
            'to' => $token->expo_push_token,
            'sound' => 'default',
            'title' => 'RHS notification test',
            'body' => 'Votre appareil recoit bien les notifications push RHS.',
            'data' => ['target' => 'dashboard', 'key' => 'test'],
        ])->all());

        $this->info('Sent test push to ' . $tokens->count() . ' registered device(s).');

        return self::SUCCESS;
    }
}
