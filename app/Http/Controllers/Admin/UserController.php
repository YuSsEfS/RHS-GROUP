<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $role = trim((string) $request->query('role', 'all'));
        $status = trim((string) $request->query('status', 'all'));

        $users = User::query()
            ->with('approver')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($role !== '' && $role !== 'all', fn ($query) => $query->where('role', $role))
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
            'role' => $role,
            'status' => $status,
            'roles' => User::availableRoles(),
            'statuses' => User::availableStatuses(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', $this->formData(new User([
            'role' => User::ROLE_EMPLOYEE,
            'status' => User::STATUS_PENDING,
            'permissions' => [],
        ])));
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request);

        $user = new User();
        $this->fillUser($user, $validated, true);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', $this->formData($user));
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validateUser($request, $user);
        $this->fillUser($user, $validated, false);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        abort_if($user->is(auth()->user()), 422, 'Vous ne pouvez pas supprimer votre propre compte.');

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => $passwordRules,
            'role' => ['required', Rule::in(array_keys(User::availableRoles()))],
            'status' => ['required', Rule::in(array_keys(User::availableStatuses()))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(User::availablePermissions()))],
        ]);
    }

    private function fillUser(User $user, array $validated, bool $creating): void
    {
        $status = $validated['status'];

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $status,
            'permissions' => array_values($validated['permissions'] ?? []),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        if ($status === User::STATUS_APPROVED) {
            $payload['approved_at'] = $user->approved_at ?: now();
            $payload['approved_by'] = auth()->id();
        } else {
            $payload['approved_at'] = null;
            $payload['approved_by'] = $status === User::STATUS_REJECTED ? auth()->id() : null;
        }

        if ($creating) {
            $user->fill($payload)->save();
            return;
        }

        $user->fill($payload)->save();
    }

    private function formData(User $user): array
    {
        return [
            'user' => $user,
            'roles' => User::availableRoles(),
            'statuses' => User::availableStatuses(),
            'permissions' => User::availablePermissions(),
        ];
    }
}
