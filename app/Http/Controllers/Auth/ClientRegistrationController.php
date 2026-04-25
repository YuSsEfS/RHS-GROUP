<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.client-register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_CLIENT,
            'status' => User::STATUS_PENDING,
            'permissions' => [],
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return redirect()
            ->route('login')
            ->with('status', 'Votre compte client a été créé et reste en attente de validation par un administrateur.');
    }
}
