<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('employee.profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
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

        return back()->with('success', 'Votre profil a ete mis a jour.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Votre mot de passe a ete mis a jour.');
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
