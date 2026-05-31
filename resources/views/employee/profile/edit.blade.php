@extends('dashboard.layouts.app')

@section('title', 'Mon profil employe')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Mon espace')
@section('page_title', 'Mon profil')
@section('page_copy', 'Mettez a jour vos informations personnelles et votre photo de profil si vous le souhaitez.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('content')
    @include('partials.portal-profile-form', ['updateRoute' => route('employee.profile.update'), 'passwordRoute' => route('employee.profile.password')])
@endsection
