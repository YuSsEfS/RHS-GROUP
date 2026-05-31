<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobilePushToken;
use App\Models\User;
use App\Services\SidebarNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index(Request $request, SidebarNotificationService $notifications)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return response()->json($notifications->forAdmin($user));
        }

        if ($user->hasAnyRole([User::ROLE_EMPLOYEE, User::ROLE_SUPERVISOR])) {
            return response()->json($notifications->forEmployee($user));
        }

        return response()->json(['items' => [], 'groups' => []]);
    }

    public function registerDevice(Request $request)
    {
        $data = $request->validate([
            'expo_push_token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:40'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $token = MobilePushToken::query()->updateOrCreate(
            ['expo_push_token' => $data['expo_push_token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'last_registered_at' => now(),
            ]
        );

        return response()->json([
            'registered' => true,
            'id' => $token->id,
        ]);
    }

    public function unregisterDevice(Request $request)
    {
        $data = $request->validate([
            'expo_push_token' => ['required', 'string', 'max:255'],
        ]);

        MobilePushToken::query()
            ->where('user_id', $request->user()->id)
            ->where('expo_push_token', $data['expo_push_token'])
            ->delete();

        return response()->json(['registered' => false]);
    }

    public function debugDevice(Request $request)
    {
        $data = $request->validate([
            'stage' => ['required', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:2000'],
            'details' => ['nullable', 'array'],
            'platform' => ['nullable', 'string', 'max:40'],
            'project_id' => ['nullable', 'string', 'max:120'],
        ]);

        Log::warning('Mobile push registration diagnostic', [
            'user_id' => $request->user()->id,
            'stage' => $data['stage'],
            'status' => $data['status'] ?? null,
            'message' => $data['message'] ?? null,
            'details' => $data['details'] ?? [],
            'platform' => $data['platform'] ?? null,
            'project_id' => $data['project_id'] ?? null,
        ]);

        return response()->json(['logged' => true]);
    }
}
