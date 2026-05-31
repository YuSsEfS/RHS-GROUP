@extends('dashboard.layouts.app')

@section('title',$resource->title)
@section('brand','RHS Employe')
@section('brand_sub','Ressources internes')
@section('sidebar')
    @include('employee._sidebar')
@endsection
@section('page_title',$resource->title)
@section('page_copy','Detail de la ressource RH.')

@section('content')
<div class="panel panel-safe">
  <div class="detail-list">
    <div><strong>Categorie</strong><span>{{ \App\Models\RhResource::categories()[$resource->category] ?? 'General' }}</span></div>
    <div><strong>Fichier</strong><span>{{ $resource->original_filename ?: '-' }}</span></div>
  </div>
  @if($resource->description)
    <div class="wrap-safe" style="margin-top:18px;">{{ $resource->description }}</div>
  @endif
  @if($resource->file_path)
    <div class="action-bar" style="margin-top:18px;">
      <a class="btn btn-primary" href="{{ route('employee.rh-resources.download', $resource) }}">Telecharger</a>
      <a class="btn btn-ghost" href="{{ route('employee.rh-resources.index') }}">Retour</a>
    </div>
  @endif
</div>
@endsection
