@extends('admin.layouts.app')

@section('title','Modifier ressource RH')
@section('page_title','Modifier ressource RH')
@section('page_subtitle','Mettez a jour le contenu et la visibilite.')

@section('content')
<div class="panel panel-safe rh-resource-form-panel">
  <form method="POST" action="{{ route('admin.rh-resources.update', $resource) }}" enctype="multipart/form-data" class="form-grid rh-resource-form">
    @csrf
    @method('PUT')
    @include('admin.rh_resources.form')
    <div class="action-bar">
      <button type="submit" class="btn btn-primary">Enregistrer</button>
      <a href="{{ route('admin.rh-resources.show', $resource) }}" class="btn btn-ghost">Annuler</a>
    </div>
  </form>
</div>
@endsection
