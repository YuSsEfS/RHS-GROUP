@extends('dashboard.layouts.app')

@section('title','Nouvelle reunion')
@section('brand','RHS Employe')
@section('brand_sub','Planning interne')
@section('sidebar')
    @include('employee._sidebar')
@endsection
@section('page_title','Nouvelle reunion')
@section('page_copy','Planifiez un echange interne avec les personnes autorisees.')

@section('content')
<div class="panel panel-safe meeting-form-panel">
  <form method="POST" action="{{ route('employee.meetings.store') }}" class="stack-form meeting-form">
    @csrf
    @include('admin.meetings.form')
    <div class="form-actions">
      <a class="btn btn-ghost" href="{{ route('employee.meetings.index') }}">Annuler</a>
      <button class="btn btn-primary" type="submit">Creer la reunion</button>
    </div>
  </form>
</div>
@endsection
