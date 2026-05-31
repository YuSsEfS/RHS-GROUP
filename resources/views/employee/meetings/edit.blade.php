@extends('dashboard.layouts.app')

@section('title','Modifier la reunion')
@section('brand','RHS Employe')
@section('brand_sub','Planning interne')
@section('sidebar')
    @include('employee._sidebar')
@endsection
@section('page_title','Modifier la reunion')
@section('page_copy','Mettez a jour les informations de votre reunion.')

@section('content')
<div class="panel panel-safe meeting-form-panel">
  <form method="POST" action="{{ route('employee.meetings.update', $meeting) }}" class="stack-form meeting-form">
    @csrf
    @method('PUT')
    @include('admin.meetings.form')
    <div class="form-actions">
      <a class="btn btn-ghost" href="{{ route('employee.meetings.show', $meeting) }}">Annuler</a>
      <button class="btn btn-primary" type="submit">Enregistrer</button>
    </div>
  </form>
</div>
@endsection
