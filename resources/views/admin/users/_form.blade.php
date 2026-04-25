@csrf

<div class="admin-grid" style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px;">
    <div style="grid-column:1 / -1;">
        <label class="admin-label" for="name">Nom</label>
        <input class="admin-input" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required style="width:100%;">
    </div>

    <div>
        <label class="admin-label" for="email">Email</label>
        <input class="admin-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required style="width:100%;">
    </div>

    <div>
        <label class="admin-label" for="role">Rôle</label>
        <select class="admin-input" id="role" name="role" required style="width:100%; height:44px;">
            @foreach($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="admin-label" for="status">Statut</label>
        <select class="admin-input" id="status" name="status" required style="width:100%; height:44px;">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $user->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="admin-label" for="password">Mot de passe {{ $user->exists ? '(laisser vide pour conserver)' : '' }}</label>
        <input class="admin-input" id="password" name="password" type="password" {{ $user->exists ? '' : 'required' }} style="width:100%;">
    </div>

    <div style="grid-column:1 / -1;">
        <label class="admin-label" for="password_confirmation">Confirmation du mot de passe</label>
        <input class="admin-input" id="password_confirmation" name="password_confirmation" type="password" style="width:100%;">
    </div>

    <div style="grid-column:1 / -1;">
        <div class="admin-card" style="padding:18px;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:12px; flex-wrap:wrap;">
                <div>
                    <h3 style="margin:0 0 6px;">Permissions modules</h3>
                    <p style="margin:0; color:#64748b; line-height:1.6;">
                        Les permissions restent configurables pour tous les roles, y compris les clients. L acces final depend ensuite du role, du middleware et des permissions attribuees.
                    </p>
                </div>
            </div>

            <div class="checkbox-grid">
                @foreach($permissions as $value => $label)
                    <label class="checkbox-card">
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
