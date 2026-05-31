<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides.'],
            ]);
        }

        if (!$user->isApproved() && !$user->isAdmin()) {
            return response()->json(['message' => 'Compte en attente d approbation.'], 403);
        }

        if (!method_exists($user, 'createToken')) {
            return response()->json([
                'message' => 'Laravel Sanctum doit etre installe et active pour generer des tokens mobiles.',
            ], 503);
        }

        return response()->json([
            'token' => $user->createToken($credentials['device_name'] ?? 'mobile')->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'remove_profile_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_profile_photo') || $request->hasFile('profile_photo')) {
            $this->deleteProfilePhoto($user);
            $data['profile_photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        $user->update($data);

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Deconnecte.']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'permissions' => $user->permissionKeys(),
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }

    private function deleteProfilePhoto(User $user): void
    {
        if (!$user->profile_photo_path) {
            return;
        }

        if (Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }
    }
}
