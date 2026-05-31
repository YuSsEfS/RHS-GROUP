@extends('admin.layouts.app')

@section('title','Ressources RH')
@section('page_title','Ressources RH')
@section('page_subtitle','Centralisez les documents internes visibles par role.')

@section('top_actions')
<a href="{{ route('admin.rh-resources.create') }}" class="btn btn-primary">Nouvelle ressource</a>
@endsection

@section('content')
<div class="rh-resource-index-page">
<div class="panel panel-safe rh-resource-panel">
  <div class="panel-head">
    <div class="panel-title">Ressources <span class="panel-badge">{{ $resources->total() }}</span></div>
  </div>

  <form method="GET" class="filter-panel action-bar">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Titre, categorie...">
    <select name="category">
      <option value="">Toutes categories</option>
      @foreach($categories as $key => $label)
        <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
      @endforeach
    </select>
    <select name="status">
      <option value="">Tous statuts</option>
      <option value="active" @selected(request('status') === 'active')>Actives</option>
      <option value="inactive" @selected(request('status') === 'inactive')>Inactives</option>
    </select>
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-ghost" href="{{ route('admin.rh-resources.index') }}">Reinitialiser</a>
  </form>

  <div class="table-wrap table-safe">
    <table class="table">
      <thead>
        <tr>
          <th>Titre</th>
          <th>Categorie</th>
          <th>Visibilite</th>
          <th>Fichier</th>
          <th>Statut</th>
          <th class="th-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($resources as $resource)
          <tr>
            <td>
              <strong>{{ $resource->title }}</strong>
              <div class="text-muted wrap-safe">{{ $resource->description }}</div>
            </td>
            <td>{{ $categories[$resource->category] ?? '-' }}</td>
            <td>{{ collect($resource->visibility_roles ?: [])->map(fn ($role) => $roles[$role] ?? $role)->join(', ') ?: 'Tous' }}</td>
            <td>{{ $resource->original_filename ?: '-' }}</td>
            <td><span class="badge">{{ $resource->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="td-actions">
              <a class="btn btn-ghost btn-sm" href="{{ route('admin.rh-resources.show', $resource) }}">Ouvrir</a>
              <a class="btn btn-ghost btn-sm" href="{{ route('admin.rh-resources.edit', $resource) }}">Modifier</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6">
              <div class="table-empty">
                <div class="table-empty-title">Aucune ressource RH</div>
                <div class="table-empty-sub">Ajoutez des guides, documents ou procedures internes.</div>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="panel-foot">{{ $resources->links() }}</div>
</div>
</div>
@endsection
