<div class="form-row">
  <label>Titre</label>
  <input type="text" name="title" value="{{ old('title', $resource->title) }}" required>
</div>

<div class="form-row">
  <label>Description</label>
  <textarea name="description" rows="4">{{ old('description', $resource->description) }}</textarea>
</div>

<div class="grid-2">
  <div class="form-row">
    <label>Categorie</label>
    <select name="category">
      <option value="">Non classee</option>
      @foreach($categories as $key => $label)
        <option value="{{ $key }}" @selected(old('category', $resource->category) === $key)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-row">
    <label>Statut</label>
    <label class="permission-card">
      <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $resource->is_active))>
      <span>Ressource active</span>
    </label>
  </div>
</div>

<div class="form-row">
  <label>Visibilite</label>
  <div class="permission-grid">
    @foreach($roles as $role => $label)
      <label class="permission-card">
        <input
          type="checkbox"
          name="visibility_roles[]"
          value="{{ $role }}"
          @checked(in_array($role, old('visibility_roles', $resource->visibility_roles ?: [])))
        >
        <span>{{ $label }}</span>
      </label>
    @endforeach
  </div>
  <p class="form-help">Si aucun role n est coche, la ressource sera visible par tous les employes autorises.</p>
</div>

<div class="form-row">
  <label>Fichier</label>
  <label class="rhs-file-card" for="rh_resource_file">
    <span class="rhs-file-card-icon">+</span>
    <span>
      <strong>Ajouter un fichier RH</strong>
      <small>PDF, document, image ou support interne.</small>
    </span>
  </label>
  <input id="rh_resource_file" type="file" name="file" class="rhs-file-card-input">
  @if($resource->original_filename)
    <p class="form-help">Fichier actuel : {{ $resource->original_filename }}</p>
  @endif
</div>
