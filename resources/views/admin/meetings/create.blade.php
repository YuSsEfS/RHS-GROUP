@extends('admin.layouts.app')

@section('title','Nouvelle reunion')
@section('page_title','Nouvelle reunion')
@section('page_subtitle','Ajoutez une reunion et notifiez les participants selectionnes.')

@section('content')
<div class="panel panel-safe meeting-form-panel">
  <form method="POST" action="{{ route('admin.meetings.store') }}" class="form-grid meeting-form">
    @csrf
    @include('admin.meetings.form', ['meeting' => null, 'selectedParticipants' => []])
    <div class="action-bar">
      <button type="submit" class="btn btn-primary">Planifier</button>
      <a href="{{ route('admin.meetings.index') }}" class="btn btn-ghost">Annuler</a>
    </div>
  </form>
</div>
@endsection
