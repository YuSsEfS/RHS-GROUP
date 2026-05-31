@extends('admin.layouts.app')

@section('title',$resource->title)
@section('page_title',$resource->title)
@section('page_subtitle','Detail de la ressource RH.')

@section('top_actions')
<a href="{{ route('admin.rh-resources.edit', $resource) }}" class="btn btn-primary">Modifier</a>
@if($resource->file_path)
  <a href="{{ route('admin.rh-resources.download', $resource) }}" class="btn btn-ghost">Telecharger</a>
@endif
<a href="{{ route('admin.rh-resources.index') }}" class="btn btn-ghost">Retour</a>
@endsection

@section('content')
<div class="panel panel-safe">
  <div class="detail-list">
    <div><strong>Categorie</strong><span>{{ \App\Models\RhResource::categories()[$resource->category] ?? '-' }}</span></div>
    <div><strong>Statut</strong><span>{{ $resource->is_active ? 'Active' : 'Inactive' }}</span></div>
    <div><strong>Fichier</strong><span>{{ $resource->original_filename ?: '-' }}</span></div>
    <div><strong>Cree par</strong><span>{{ $resource->creator?->name ?: '-' }}</span></div>
  </div>
  @if($resource->description)
    <div class="wrap-safe" style="margin-top:18px;">{{ $resource->description }}</div>
  @endif
</div>
@endsection
