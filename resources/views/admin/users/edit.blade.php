@extends('admin.layouts.app')

@section('title', 'Modifier un utilisateur')
@section('page_title', 'Modifier un utilisateur')
@section('page_subtitle', 'Mise à jour du rôle, du statut de validation et des permissions.')

@section('content')
    <div class="admin-card" style="padding:24px;">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @method('PUT')
            @include('admin.users._form', ['submitLabel' => 'Enregistrer'])
        </form>
    </div>
@endsection
