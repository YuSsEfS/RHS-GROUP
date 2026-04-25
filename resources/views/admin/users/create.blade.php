@extends('admin.layouts.app')

@section('title', 'Créer un utilisateur')
@section('page_title', 'Créer un utilisateur')
@section('page_subtitle', 'Création manuelle d’un compte admin, employee ou client.')

@section('content')
    <div class="admin-card" style="padding:24px;">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @include('admin.users._form', ['submitLabel' => 'Créer'])
        </form>
    </div>
@endsection
