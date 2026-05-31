<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();

        return view('admin.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

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
        auth()->setUser($user->fresh());

        return back()->with('success', 'Profil mis a jour.');
    }

    public function password(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Mot de passe mis a jour.');
    }

    private function deleteProfilePhoto($user): void
    {
        if (!$user->profile_photo_path) {
            return;
        }

        if (Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }
    }
}
