@extends('admin.layouts.app')

@section('title','Modifier reunion')
@section('page_title','Modifier reunion')
@section('page_subtitle','Mettez a jour le planning et les participants.')

@section('content')
<div class="panel panel-safe meeting-form-panel">
  <form method="POST" action="{{ route('admin.meetings.update', $meeting) }}" class="form-grid meeting-form">
    @csrf
    @method('PUT')
    @include('admin.meetings.form', ['meeting' => $meeting])
    <div class="action-bar">
      <button type="submit" class="btn btn-primary">Enregistrer</button>
      <a href="{{ route('admin.meetings.show', $meeting) }}" class="btn btn-ghost">Annuler</a>
    </div>
  </form>
</div>
@endsection
