@extends('dashboard.layouts.app')

@section('title','Ressources RH')
@section('brand','RHS Employe')
@section('brand_sub','Ressources internes')
@section('sidebar')
    @include('employee._sidebar')
@endsection
@section('page_title','Ressources RH')
@section('page_copy','Retrouvez les documents, procedures et informations utiles.')

@section('content')
<div class="panel panel-safe">
  <form method="GET" class="filter-panel action-bar">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher une ressource...">
    <select name="category">
      <option value="">Toutes categories</option>
      @foreach($categories as $key => $label)
        <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
      @endforeach
    </select>
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-ghost" href="{{ route('employee.rh-resources.index') }}">Reinitialiser</a>
  </form>

  <div class="resource-grid">
    @forelse($resources as $resource)
      <article class="panel panel-safe">
        <span class="badge">{{ $categories[$resource->category] ?? 'General' }}</span>
        <h3>{{ $resource->title }}</h3>
        <p class="wrap-safe">{{ $resource->description }}</p>
        <div class="action-bar">
          <a class="btn btn-ghost btn-sm" href="{{ route('employee.rh-resources.show', $resource) }}">Ouvrir</a>
          @if($resource->file_path)
            <a class="btn btn-primary btn-sm" href="{{ route('employee.rh-resources.download', $resource) }}">Telecharger</a>
          @endif
        </div>
      </article>
    @empty
      <div class="table-empty">Aucune ressource disponible pour votre profil.</div>
    @endforelse
  </div>

  <div class="panel-foot">{{ $resources->links() }}</div>
</div>
@endsection
