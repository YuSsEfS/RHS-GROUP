@csrf

<div class="admin-grid">
    <div class="ui-span-full">
        <label class="admin-label" for="name">Nom</label>
        <input class="admin-input" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
    </div>

    <div>
        <label class="admin-label" for="email">Email</label>
        <input class="admin-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
    </div>

    <div>
        <label class="admin-label" for="role">Rôle</label>
        <select class="admin-input" id="role" name="role" required>
            @foreach($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="admin-label" for="status">Statut</label>
        <select class="admin-input" id="status" name="status" required>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $user->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="admin-label" for="password">Mot de passe {{ $user->exists ? '(laisser vide pour conserver)' : '' }}</label>
        <input class="admin-input" id="password" name="password" type="password" {{ $user->exists ? '' : 'required' }}>
    </div>

    <div class="ui-span-full">
        <label class="admin-label" for="password_confirmation">Confirmation du mot de passe</label>
        <input class="admin-input" id="password_confirmation" name="password_confirmation" type="password">
    </div>

    <div class="ui-span-full">
        <div class="admin-card" style="padding:18px;">
            <div class="ui-toolbar" style="margin-bottom:12px;">
                <div>
                    <h3 style="margin:0 0 6px;">Permissions modules</h3>
                    <p style="margin:0; color:#64748b; line-height:1.6;">
                        Les permissions visibles s adaptent au role choisi. Les clients restent limites a leurs demandes de recrutement et les employes ne voient que les modules reels qui leur sont attribues.
                    </p>
                </div>
            </div>

            <div class="checkbox-grid">
                @foreach($permissions as $value => $label)
                    <label class="checkbox-card" data-permission-card data-roles="{{ implode(',', $permissionRoleMap[$value] ?? []) }}">
                        <input type="checkbox" name="permissions[]" value="{{ $value }}"
                            @checked(in_array($value, old('permissions', $user->permissions ?? []), true))>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="action-row" style="margin-top:24px;">
    <button type="submit" class="admin-btn admin-btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-ghost">Annuler</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleInput = document.getElementById('role');
    const permissionCards = document.querySelectorAll('[data-permission-card]');

    if (!roleInput || !permissionCards.length) {
        return;
    }

    const syncPermissionCards = function () {
        const role = roleInput.value;

        permissionCards.forEach(function (card) {
            const roles = (card.dataset.roles || '')
                .split(',')
                .map(function (value) { return value.trim(); })
                .filter(Boolean);
            const checkbox = card.querySelector('input[type="checkbox"]');
            const allowed = role === 'admin' || roles.includes(role);

            card.style.display = allowed ? '' : 'none';

            if (checkbox) {
                checkbox.disabled = !allowed;

                if (!allowed) {
                    checkbox.checked = false;
                }
            }
        });
    };

    roleInput.addEventListener('change', syncPermissionCards);
    syncPermissionCards();
});
</script>
@endpush
