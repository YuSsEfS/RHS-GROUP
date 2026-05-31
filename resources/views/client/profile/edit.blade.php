@extends('dashboard.layouts.app')

@section('title', 'Mon profil client')
@section('brand', 'RHS Client')
@section('brand_sub', 'Mon espace')
@section('page_title', 'Mon profil')
@section('page_copy', 'Mettez a jour vos informations et ajoutez une photo de profil si vous le souhaitez.')

@section('sidebar')
    @include('client._sidebar')
@endsection

@section('content')
    @include('partials.portal-profile-form', ['updateRoute' => route('client.profile.update'), 'passwordRoute' => route('client.profile.password')])
@endsection
