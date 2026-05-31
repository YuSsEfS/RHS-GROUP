@extends('admin.layouts.app')

@section('title','Nouvelle ressource RH')
@section('page_title','Nouvelle ressource RH')
@section('page_subtitle','Publiez un document ou une information interne.')

@section('content')
<div class="panel panel-safe rh-resource-form-panel">
  <form method="POST" action="{{ route('admin.rh-resources.store') }}" enctype="multipart/form-data" class="form-grid rh-resource-form">
    @csrf
    @include('admin.rh_resources.form')
    <div class="action-bar">
      <button type="submit" class="btn btn-primary">Creer</button>
      <a href="{{ route('admin.rh-resources.index') }}" class="btn btn-ghost">Annuler</a>
    </div>
  </form>
</div>
@endsection
