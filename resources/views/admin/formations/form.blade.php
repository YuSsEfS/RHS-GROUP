<div class="form-grid">

  {{-- TITRE --}}
  <div class="form-field">
    <label for="title">Titre</label>
    <input
      id="title"
      name="title"
      type="text"
      value="{{ old('title', $formation->title ?? '') }}"
      placeholder="Ex: Développement Web Full Stack"
      required
    >
  </div>

  {{-- DOMAINE --}}
  <div class="form-field">
    <label for="domain">Domaine</label>
    <input
      id="domain"
      name="domain"
      type="text"
      value="{{ old('domain', $formation->domain ?? '') }}"
      placeholder="Ex: Informatique, Marketing..."
    >
  </div>

  {{-- PUBLIC --}}
  <div class="form-field">
    <label for="public">Public cible</label>
    <input
      id="public"
      name="public"
      type="text"
      value="{{ old('public', $formation->public ?? '') }}"
      placeholder="Ex: Étudiants, Professionnels"
    >
  </div>

  {{-- FORMAT --}}
  <div class="form-field">
    <label for="format">Format</label>
    <input
      id="format"
      name="format"
      type="text"
      value="{{ old('format', $formation->format ?? '') }}"
      placeholder="Ex: Présentiel, En ligne"
    >
  </div>

  {{-- DURÉE --}}
  <div class="form-field">
    <label for="duration">Durée</label>
    <input
      id="duration"
      name="duration"
      type="text"
      value="{{ old('duration', $formation->duration ?? '') }}"
      placeholder="Ex: 3 jours, 40 heures"
    >
  </div>

  {{-- AUDIENCE --}}
  <div class="form-field">
    <label for="audience">Audience</label>
    <input
      id="audience"
      name="audience"
      type="text"
      value="{{ old('audience', $formation->audience ?? '') }}"
      placeholder="Ex: Débutants, Avancés"
    >
  </div>

  {{-- FORMAT LABEL --}}
  <div class="form-field">
    <label for="format_label">Label du format</label>
    <input
      id="format_label"
      name="format_label"
      type="text"
      value="{{ old('format_label', $formation->format_label ?? '') }}"
      placeholder="Ex: Certifié, Diplôme"
    >
  </div>

  {{-- DESCRIPTION --}}
  <div class="form-field full">
    <label for="description">Description</label>
    <textarea
      id="description"
      name="description"
      rows="5"
      placeholder="Description complète de la formation"
    >{{ old('description', $formation->description ?? '') }}</textarea>
  </div>

  {{-- PROGRAMME --}}
  <div class="form-field full">
    <label for="program">Programme</label>
    <textarea
      id="program"
      name="program"
      rows="6"
      placeholder="Détaillez le programme de la formation"
    >{{ old('program', $formation->program ?? '') }}</textarea>
  </div>

  {{-- FEATURED --}}
  <div class="form-field switch-field">
    <label>Formation mise en avant</label>
    <label class="switch">
      <input type="checkbox" name="featured" value="1" {{ old('featured', $formation->featured ?? false) ? 'checked' : '' }}>
      <span class="slider"></span>
    </label>
  </div>

</div>
