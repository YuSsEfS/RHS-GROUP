<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ResolvesUserTimezone;

    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $timezone = $this->userTimezone($request);

        $users = User::query()
            ->latest()
            ->paginate(30)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'online' => false,
                'profile_photo_url' => $user->profile_photo_url,
                'created_at' => $this->localDate($user->created_at, $timezone),
                'timezone' => $timezone,
            ]);

        return response()->json($users);
    }
}
