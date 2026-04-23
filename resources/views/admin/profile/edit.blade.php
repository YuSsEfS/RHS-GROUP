@extends('admin.layouts.app')
@section('title','Admin – Profil')
@section('page_title','Mon profil')

@section('page_subtitle')
Modifiez votre email et votre mot de passe
@endsection

@section('content')

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">Informations</div>
    </div>

    <div class="panel-body">
      <form method="POST" action="{{ route('admin.profile.update') }}" class="form">
        @csrf
        @method('PATCH')

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
