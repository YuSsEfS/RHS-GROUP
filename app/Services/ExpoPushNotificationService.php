<?php

namespace App\Services;

use App\Models\MobilePushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public function send(array $messages): void
    {
        collect($messages)
            ->filter(fn ($message) => !empty($message['to']))
            ->chunk(100)
            ->each(function ($chunk) {
                $messages = $chunk->values()->all();
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->post(self::ENDPOINT, $messages);

                if (!$response->successful()) {
                    Log::warning('Expo push notification request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return;
                }

                $this->handleTickets($response->json('data') ?? [], $messages);
            });
    }

    private function handleTickets(array $tickets, array $messages): void
    {
        foreach ($tickets as $index => $ticket) {
            $details = is_array($ticket) ? ($ticket['details'] ?? []) : [];

            if (($ticket['status'] ?? null) !== 'error') {
                continue;
            }

            Log::warning('Expo push notification rejected', [
                'message' => $ticket['message'] ?? null,
                'details' => $details,
            ]);

            if (($details['error'] ?? null) === 'DeviceNotRegistered' && isset($messages[$index]['to'])) {
                MobilePushToken::query()->where('expo_push_token', $messages[$index]['to'])->delete();
            }
        }
    }
}
