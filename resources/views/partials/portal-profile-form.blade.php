<div class="portal-split">
    <section class="portal-card">
        <div class="portal-record-top">
            <div>
                <h3 class="portal-title-tight">Informations</h3>
                <p class="portal-copy portal-copy-tight">Email et photo de profil.</p>
            </div>
            <div class="profile-avatar-lg">
                @if($user->profile_photo_url)
                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                @else
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                @endif
            </div>
        </div>

        <form method="POST" action="{{ $updateRoute }}" class="portal-form-grid" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <input type="hidden" name="name" value="{{ old('name', $user->name) }}">
            <div>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="full">
                <label for="profile_photo">Photo de profil (optionnelle)</label>
                <div class="portal-file-field">
                    <input id="profile_photo" name="profile_photo" type="file" class="portal-file-input">
                    <div class="portal-field-help">Image JPG, PNG ou WebP. Taille maximale: 5 Mo.</div>
                </div>
            </div>
            @if($user->profile_photo_path)
                <div class="full">
                    <label class="rhs-checkbox-card" style="display:flex;align-items:center;gap:10px;">
                        <input type="checkbox" name="remove_profile_photo" value="1">
                        <span>Supprimer la photo actuelle</span>
                    </label>
                    <div class="portal-field-help">Si vous choisissez une nouvelle photo, l'ancienne sera remplacee automatiquement.</div>
                </div>
            @endif
            <div class="full portal-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary portal-btn-auto">Enregistrer</button>
            </div>
        </form>
    </section>

    <section class="portal-card">
        <h3 class="portal-title-tight">Securite</h3>
        <p class="portal-copy portal-copy-tight">Changez votre mot de passe si necessaire.</p>
        <form method="POST" action="{{ $passwordRoute }}" class="portal-form-grid" style="margin-top:18px;">
            @csrf
            @method('PATCH')
            <div class="full">
                <label for="current_password">Mot de passe actuel</label>
                <input id="current_password" type="password" name="current_password" required>
            </div>
            <div>
                <label for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" required minlength="8">
            </div>
            <div>
                <label for="password_confirmation">Confirmation</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8">
            </div>
            <div class="full portal-form-actions">
                <button type="submit" class="admin-btn admin-btn-ghost portal-btn-auto">Mettre a jour</button>
            </div>
        </form>
    </section>
</div>
