@extends('admin.layouts.app')
@section('title','Admin – Profil')
@section('page_title','Mon profil')

@section('page_subtitle')
Modifiez votre email et votre mot de passe
@endsection

@section('content')

  @push('styles')
    <style>
      .admin-profile-edit-grid {
        display:grid;
        grid-template-columns:180px minmax(0, 1fr);
        gap:22px;
        align-items:start;
      }

      .admin-profile-edit-grid > .form-label {
        grid-row:1 / span 2;
      }

      .admin-profile-edit-grid > div {
        min-width:0;
      }

      .admin-profile-photo-stage {
        display:grid;
        place-items:center;
        padding:18px;
        border-radius:26px;
        background:linear-gradient(135deg, #fff7f8, #eef3f8);
        border:1px solid rgba(219,229,239,.95);
      }

      .admin-profile-upload-card {
        min-height:112px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:18px;
        padding:18px;
        border:1px dashed rgba(239,35,60,.30);
        border-radius:22px;
        background:linear-gradient(135deg, rgba(255,255,255,.98), rgba(255,241,242,.72));
        cursor:pointer;
        box-shadow:0 18px 44px rgba(15,23,42,.06);
        transition:border-color .18s ease, transform .18s ease, box-shadow .18s ease;
        width:100%;
        max-width:520px;
      }

      .admin-profile-upload-card:hover,
      .admin-profile-upload-card:focus-within {
        border-color:rgba(239,35,60,.62);
        transform:translateY(-1px);
        box-shadow:0 24px 56px rgba(239,35,60,.10);
      }

      .admin-profile-upload-main {
        display:grid;
        gap:6px;
        min-width:0;
      }

      .admin-profile-upload-main strong {
        color:#06142d;
        font-size:15px;
        font-weight:950;
      }

      .admin-profile-upload-main small {
        color:#64748b;
        font-weight:750;
      }

      .admin-profile-upload-action {
        flex:0 0 auto;
        min-height:44px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        padding:0 16px;
        border-radius:999px;
        background:#ef233c;
        color:#fff;
        font-weight:950;
        box-shadow:0 18px 36px rgba(239,35,60,.18);
      }

      @media (max-width: 760px) {
        .admin-profile-edit-grid {
          grid-template-columns:1fr;
        }
      }
    </style>
  @endpush

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Informations</div>
    </div>

    <div class="panel-body">
      <form method="POST" action="{{ route('admin.profile.update') }}" class="form" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="form-row admin-profile-edit-grid">
          <label class="form-label">Photo de profil</label>
          <div>
            <div class="admin-profile-photo-stage">
              <div class="profile-avatar-lg">
                @if($user->profile_photo_url)
                  <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                @else
                  {{ strtoupper(substr($user->name, 0, 1)) }}
                @endif
              </div>
            </div>
          </div>
          <div>
            <input class="rhs-file-card-input" id="admin-profile-photo-input" type="file" name="profile_photo" accept="image/*">
            <label class="rhs-file-card admin-profile-upload-card" for="admin-profile-photo-input">
              <span class="admin-profile-upload-main">
                <strong>Choisir une nouvelle photo</strong>
                <small>JPG, PNG ou WebP. Taille maximale: 5 Mo.</small>
              </span>
              <span class="admin-profile-upload-action">Parcourir</span>
            </label>
            <div class="form-help">La photo sera recadree visuellement dans les avatars du dashboard.</div>
          </div>
        </div>

        @if($user->profile_photo_path)
          <div class="form-row">
            <label class="rhs-checkbox-card" style="display:flex;align-items:center;gap:10px;">
              <input type="checkbox" name="remove_profile_photo" value="1">
              <span>Supprimer la photo actuelle</span>
            </label>
          </div>
        @endif

        <div class="form-row">
          <label class="form-label">Nom</label>
          <input class="form-input" type="text" name="name" value="{{ old('name', $user->name) }}" required>
        </div>

        <div class="form-row">
          <label class="form-label">Email</label>
          <input class="form-input" type="email" name="email" value="{{ old('email', $user->email) }}" required>
        </div>

        <div class="form-actions">
          <button class="btn btn-primary" type="submit">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <div class="panel" style="margin-top:16px;">
    <div class="panel-head">
      <div class="panel-title">Sécurité</div>
    </div>

    <div class="panel-body">
      <form method="POST" action="{{ route('admin.profile.password') }}" class="form">
        @csrf
        @method('PATCH')

        <div class="form-row">
          <label class="form-label">Mot de passe actuel</label>
          <input class="form-input" type="password" name="current_password" required>
        </div>

        <div class="form-row">
          <label class="form-label">Nouveau mot de passe</label>
          <input class="form-input" type="password" name="password" required minlength="8">
        </div>

        <div class="form-row">
          <label class="form-label">Confirmer le mot de passe</label>
          <input class="form-input" type="password" name="password_confirmation" required minlength="8">
        </div>

        <div class="form-actions">
          <button class="btn btn-primary" type="submit">Mettre à jour</button>
        </div>
      </form>
    </div>
  </div>

@endsection
