@csrf

<div class="form-grid">

  {{-- TITRE --}}
  <div class="form-field">
    <label for="title">Titre</label>
    <input
      id="title"
      name="title"
      type="text"
      value="{{ old('title', $offer->title ?? '') }}"
      required
      placeholder="Ex: Développeur Full Stack Laravel"
    >
    @error('title') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- SLUG --}}
  <div class="form-field">
    <label for="slug">Slug <span class="muted">(optionnel)</span></label>
    <input
      id="slug"
      name="slug"
      type="text"
      value="{{ old('slug', $offer->slug ?? '') }}"
      placeholder="developpeur-full-stack-laravel"
    >
    @error('slug') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- ENTREPRISE --}}
  <div class="form-field">
    <label for="company">Entreprise</label>
    <input
      id="company"
      name="company"
      type="text"
      value="{{ old('company', $offer->company ?? '') }}"
      placeholder="RHS Group"
    >
    @error('company') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- LOCALISATION --}}
  <div class="form-field">
    <label for="location">Localisation</label>
    <input
      id="location"
      name="location"
      type="text"
      value="{{ old('location', $offer->location ?? '') }}"
      placeholder="Casablanca, Maroc"
    >
    @error('location') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- CONTRAT --}}
  <div class="form-field">
    <label for="contract_type">Type de contrat</label>
    <input
      id="contract_type"
      name="contract_type"
      type="text"
      value="{{ old('contract_type', $offer->contract_type ?? '') }}"
      placeholder="CDI / CDD / Stage"
    >
    @error('contract_type') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- SECTEUR --}}
  <div class="form-field">
    <label for="sector">Secteur</label>
    <input
      id="sector"
      name="sector"
      type="text"
      value="{{ old('sector', $offer->sector ?? '') }}"
      placeholder="Informatique, Marketing, RH…"
    >
    @error('sector') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- EXCERPT --}}
  <div class="form-field full">
    <label for="excerpt">Résumé</label>
    <textarea
      id="excerpt"
      name="excerpt"
      rows="3"
      placeholder="Résumé court affiché dans la liste des offres"
    >{{ old('excerpt', $offer->excerpt ?? '') }}</textarea>
    @error('excerpt') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- DESCRIPTION --}}
  <div class="form-field full">
    <label for="description">Description</label>
    <textarea
      id="description"
      name="description"
      rows="6"
      placeholder="Description complète de l’offre"
    >{{ old('description', $offer->description ?? '') }}</textarea>
    @error('description') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- MISSIONS --}}
  <div class="form-field full">
    <label for="missions">Missions</label>
    <textarea
      id="missions"
      name="missions"
      rows="5"
      placeholder="Liste des missions principales"
    >{{ old('missions', $offer->missions ?? '') }}</textarea>
    @error('missions') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- PROFIL --}}
  <div class="form-field full">
    <label for="requirements">Profil recherché</label>
    <textarea
      id="requirements"
      name="requirements"
      rows="5"
      placeholder="Compétences, expérience, soft skills"
    >{{ old('requirements', $offer->requirements ?? '') }}</textarea>
    @error('requirements') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- HERO IMAGE --}}
  @php
    $isEdit      = isset($offer) && !empty($offer->id);
    $currentHero = $offer->hero_image ?? null;
  @endphp

  <div class="form-field full">
    <label for="hero_image">Image du header (hero)</label>

    <div class="upload-pill {{ $errors->has('hero_image') ? 'is-error' : '' }}" id="heroUpload">
      <input
        id="hero_image"
        name="hero_image"
        type="file"
        class="upload-pill-input"
        accept="image/png,image/jpeg,image/webp"
      >

      <label for="hero_image" class="upload-pill-btn">
        Choisir un fichier
      </label>

      <div class="upload-pill-hint">
        PNG / JPG / WEBP • Recommandé: 1600×900 • Max 2MB
      </div>

      <div class="upload-pill-name" id="heroFileName">
        {{ old('hero_image') ? 'Fichier sélectionné' : 'Aucun fichier choisi' }}
      </div>
    </div>

    @error('hero_image')
      <div class="form-error">{{ $message }}</div>
    @enderror

    {{-- Current image preview (edit only) --}}
    @if($currentHero)
      <div class="upload-preview" id="heroCurrentPreview">
        <div class="upload-preview-label">Image actuelle :</div>
        <img
          src="{{ asset('storage/'.$currentHero) }}"
          alt="Hero image"
        >
      </div>
    @endif

    {{-- Live preview of the newly selected image --}}
    <div class="upload-preview" id="heroNewPreview" style="display:none;">
      <div class="upload-preview-label">Nouvelle image :</div>
      <img id="heroNewImg" src="" alt="New hero preview">
    </div>
  </div>

  {{-- DATE --}}
  @php
    $publishedValue = old('published_at');

    if ($publishedValue === null) {
      $raw = $offer->published_at ?? null;

      if ($raw) {
        try {
          $publishedValue = \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {
          $publishedValue = '';
        }
      } else {
        $publishedValue = '';
      }
    }
  @endphp

  <div class="form-field">
    <label for="published_at">Publié le</label>
    <input
      id="published_at"
      type="date"
      name="published_at"
      value="{{ $publishedValue }}"
    >
    @error('published_at') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  {{-- ACTIVE --}}
  <div class="form-field switch-field">
    <label>Statut</label>
    <label class="switch">
      <input
        type="checkbox"
        name="is_active"
        value="1"
        {{ old('is_active', ($offer->is_active ?? true)) ? 'checked' : '' }}
      >
      <span class="slider"></span>
    </label>
    <span class="switch-label">Offre active</span>
  </div>

</div>

@push('styles')
<style>
/* ===== Upload pill (like your CV inputs) ===== */
.upload-pill{
  position: relative;
  display:flex;
  align-items:center;
  gap: 12px;

  height: 54px;
  padding: 8px 10px;
  border-radius: 16px;

  background:
    radial-gradient(900px 260px at 12% 0%, rgba(239,68,68,.08), transparent 62%),
    #fff;

  border: 1px solid rgba(15,23,42,.12);
  box-shadow: 0 14px 34px rgba(2,6,23,.06);

  transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}

.upload-pill:hover{
  border-color: rgba(239,68,68,.22);
  box-shadow: 0 18px 46px rgba(2,6,23,.10);
  transform: translateY(-1px);
}

.upload-pill:focus-within{
  border-color: rgba(239,68,68,.45);
  box-shadow: 0 0 0 4px rgba(239,68,68,.12), 0 18px 46px rgba(2,6,23,.10);
  transform: translateY(-1px);
}

.upload-pill.is-error{
  border-color: rgba(239,68,68,.55);
  box-shadow: 0 0 0 4px rgba(239,68,68,.10), 0 18px 46px rgba(2,6,23,.08);
}

/* real input hidden */
.upload-pill-input{
  position:absolute;
  inset:0;
  opacity:0;
  cursor:pointer;
}

/* left button */
.upload-pill-btn{
  flex: 0 0 auto;
  height: 38px;
  padding: 0 14px;
  border-radius: 12px;

  display:inline-flex;
  align-items:center;
  justify-content:center;

  font-weight: 950;
  font-size: 13px;

  background: rgba(15,23,42,.04);
  border: 1px solid rgba(15,23,42,.12);
  color: #0f172a;

  cursor:pointer;
  user-select:none;

  transition: transform .15s ease, background .15s ease, border-color .15s ease;
}

.upload-pill-btn:hover{
  background: rgba(239,68,68,.08);
  border-color: rgba(239,68,68,.22);
  transform: translateY(-1px);
}

.upload-pill-hint{
  flex: 1;
  min-width: 0;
  font-size: 12px;
  font-weight: 800;
  color: #64748b;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.upload-pill-name{
  flex: 0 0 auto;
  font-size: 13px;
  font-weight: 900;
  color: #334155;

  max-width: 40%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;

  padding-right: 6px;
}

/* drag state */
.upload-pill.is-dragover{
  border-color: rgba(239,68,68,.45);
  background:
    radial-gradient(900px 260px at 12% 0%, rgba(239,68,68,.12), transparent 62%),
    #fff;
}

/* Preview (keeps your style) */
.upload-preview{
  margin-top: 12px;
  padding: 12px;
  border: 1px solid rgba(15,23,42,.08);
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 14px 36px rgba(2,6,23,.06);
}
.upload-preview-label{
  font-size: 12px;
  font-weight: 950;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .06em;
  margin-bottom: 10px;
}
.upload-preview img{
  width: 100%;
  max-height: 280px;
  object-fit: cover;
  border-radius: 16px;
  border: 1px solid rgba(15,23,42,.08);
}

/* Responsive */
@media (max-width: 700px){
  .upload-pill{
    height: auto;
    padding: 12px;
    flex-direction: column;
    align-items: stretch;
  }
  .upload-pill-btn{ width: 100%; height: 44px; }
  .upload-pill-name{ max-width: 100%; text-align:center; }
  .upload-pill-hint{ text-align:center; }
}
</style>
@endpush

@push('scripts')
<script>
  (function(){
    const wrap   = document.getElementById('heroUpload');
    const input  = document.getElementById('hero_image');
    const nameEl = document.getElementById('heroFileName');

    const newPreview = document.getElementById('heroNewPreview');
    const newImg     = document.getElementById('heroNewImg');
    const currentPreview = document.getElementById('heroCurrentPreview');

    if(!wrap || !input || !nameEl) return;

    function setNameAndPreview(){
      const file = input.files && input.files[0];

      nameEl.textContent = file ? file.name : 'Aucun fichier choisi';

      // live preview for images
      if(file && file.type && file.type.startsWith('image/')){
        const url = URL.createObjectURL(file);
        if(newImg) newImg.src = url;
        if(newPreview) newPreview.style.display = '';
        // optional: hide current preview when a new file is picked
        if(currentPreview) currentPreview.style.display = 'none';
      }else{
        if(newPreview) newPreview.style.display = 'none';
        if(currentPreview) currentPreview.style.display = '';
      }
    }

    input.addEventListener('change', setNameAndPreview);

    // drag & drop
    ['dragenter','dragover'].forEach(evt => {
      wrap.addEventListener(evt, e => {
        e.preventDefault();
        e.stopPropagation();
        wrap.classList.add('is-dragover');
      });
    });
    ['dragleave','drop'].forEach(evt => {
      wrap.addEventListener(evt, e => {
        e.preventDefault();
        e.stopPropagation();
        wrap.classList.remove('is-dragover');
      });
    });

    wrap.addEventListener('drop', e => {
      const files = e.dataTransfer.files;
      if(files && files.length){
        input.files = files;
        setNameAndPreview();
      }
    });

    setNameAndPreview();
  })();
</script>
@endpush
