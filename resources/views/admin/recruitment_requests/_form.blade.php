<style>
  .tag-input-wrap{
    min-height:50px;
    border:1px solid #dbe2ea;
    border-radius:14px;
    background:#fff;
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:8px;
    padding:8px 12px;
  }

  .tag-input-wrap:focus-within{
    border-color:#94a3b8;
    box-shadow:0 0 0 4px rgba(148,163,184,.14);
  }

  .tag-chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 10px;
    border-radius:10px;
    background:#e5e7eb;
    color:#111827;
    font-weight:700;
  }

  .tag-remove{
    border:0;
    background:transparent;
    cursor:pointer;
    font-size:18px;
    line-height:1;
    font-weight:900;
    color:#111827;
  }

  .tag-input{
    flex:1;
    min-width:160px;
    border:0 !important;
    outline:none !important;
    box-shadow:none !important;
    padding:8px 4px !important;
  }

  .tag-suggestions{
    position:absolute;
    z-index:50;
    left:0;
    right:0;
    top:calc(100% + 6px);
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    box-shadow:0 18px 40px rgba(15,23,42,.12);
    overflow:hidden;
    display:none;
  }

  .tag-suggestion{
    padding:12px 14px;
    cursor:pointer;
    font-weight:700;
  }

  .tag-suggestion:hover{
    background:#f8fafc;
  }

  .tag-field-relative{
    position:relative;
  }
</style>

<div class="form-grid">

  <div class="form-field">
    <label for="job_offer_id">Offre liée</label>
    <div class="select-wrapper">
      <select id="job_offer_id" name="job_offer_id" class="form-select">
        <option value="">Aucune offre liée</option>
        @foreach(($offers ?? collect()) as $offer)
          <option value="{{ $offer->id }}" {{ old('job_offer_id', $request->job_offer_id ?? '') == $offer->id ? 'selected' : '' }}>
            {{ $offer->title }}
          </option>
        @endforeach
      </select>
      <span class="select-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </div>
    @error('job_offer_id') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="cv_folder_id">Dossier CV Bank</label>
    <div class="select-wrapper">
      <select id="cv_folder_id" name="cv_folder_id" class="form-select">
        <option value="">Tous les dossiers</option>
        @foreach(($folders ?? collect()) as $folder)
          <option value="{{ $folder->id }}" {{ old('cv_folder_id', $request->cv_folder_id ?? '') == $folder->id ? 'selected' : '' }}>
            {{ $folder->name }}
          </option>
        @endforeach
      </select>
      <span class="select-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </div>
    <div class="form-help">Si vous choisissez un dossier, le matching sera lancé seulement sur les CV de ce dossier.</div>
    @error('cv_folder_id') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="reference">Affaire N°</label>
    <input id="reference" name="reference" type="text" value="{{ old('reference', $request->reference ?? '') }}" placeholder="Ex: AFF-2026-001">
    @error('reference') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="client_name">Client / Demandeur</label>
    <input id="client_name" name="client_name" type="text" value="{{ old('client_name', $request->client_name ?? '') }}" placeholder="Ex: RFM">
    @error('client_name') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="request_date">Date demande</label>
    <input id="request_date" name="request_date" type="date" value="{{ old('request_date', $request->request_date ?? '') }}">
    @error('request_date') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="position_title">Poste à pourvoir</label>
    <input id="position_title" name="position_title" type="text" value="{{ old('position_title', $request->position_title ?? '') }}" placeholder="Ex: Gestionnaire de produit" required>
    @error('position_title') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="work_location_tag_input">Lieu de travail</label>

    <div class="tag-field-relative">
      <input
        type="hidden"
        id="work_location"
        name="work_location"
        value="{{ old('work_location', $request->work_location ?? '') }}"
      >

      <div class="tag-input-wrap" id="locationTagBox">
        <input
          id="work_location_tag_input"
          type="text"
          class="tag-input"
          placeholder="Tapez une ville puis Entrée. Ex: Casablanca"
          autocomplete="off"
        >
      </div>

      <div class="tag-suggestions" id="locationSuggestions"></div>
    </div>

    <div class="form-help">Vous pouvez ajouter plusieurs lieux : Casablanca, Rabat, Tanger...</div>
    @error('work_location') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="recruitment_reason">Motif de recrutement</label>
    <input id="recruitment_reason" name="recruitment_reason" type="text" value="{{ old('recruitment_reason', $request->recruitment_reason ?? '') }}" placeholder="Ex: Remplacement">
    @error('recruitment_reason') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="age">Âge</label>
    <input id="age" name="age" type="text" value="{{ old('age', $request->age ?? '') }}" placeholder="Ex: 25-35 / moins de 40 / minimum 22">
    @error('age') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="gender">Sexe</label>
    <div class="select-wrapper">
      <select id="gender" name="gender" class="form-select">
        <option value="">Sélectionner</option>
        <option value="H" {{ old('gender', $request->gender ?? '') == 'H' ? 'selected' : '' }}>Homme</option>
        <option value="F" {{ old('gender', $request->gender ?? '') == 'F' ? 'selected' : '' }}>Femme</option>
        <option value="H/F" {{ old('gender', $request->gender ?? '') == 'H/F' ? 'selected' : '' }}>H/F</option>
      </select>
      <span class="select-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </div>
    @error('gender') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="education">Formation</label>
    <input id="education" name="education" type="text" value="{{ old('education', $request->education ?? '') }}" placeholder="Ex: Bac+3 en logistique">
    @error('education') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="experience_years">Expérience professionnelle</label>
    <input id="experience_years" name="experience_years" type="text" value="{{ old('experience_years', $request->experience_years ?? '') }}" placeholder="Ex: 2 à 3 ans / minimum 5 ans / débutant">
    @error('experience_years') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="availability">Disponibilité</label>
    <input id="availability" name="availability" type="text" value="{{ old('availability', $request->availability ?? '') }}" placeholder="Ex: ASAP">
    @error('availability') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="other_language">Autre langue</label>
    <input id="other_language" name="other_language" type="text" value="{{ old('other_language', $request->other_language ?? '') }}" placeholder="Ex: Italien">
    @error('other_language') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="budget_type">Budget du poste</label>
    <div class="select-wrapper">
      <select id="budget_type" name="budget_type" class="form-select">
        <option value="">Sélectionner</option>
        <option value="Poste budgété" {{ old('budget_type', $request->budget_type ?? '') == 'Poste budgété' ? 'selected' : '' }}>Poste budgété</option>
        <option value="Poste non budgété" {{ old('budget_type', $request->budget_type ?? '') == 'Poste non budgété' ? 'selected' : '' }}>Poste non budgété</option>
      </select>
      <span class="select-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </div>
    @error('budget_type') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="monthly_salary">Rémunération mensuelle</label>
    <input id="monthly_salary" name="monthly_salary" type="text" value="{{ old('monthly_salary', $request->monthly_salary ?? '') }}" placeholder="Ex: Négociable">
    @error('monthly_salary') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="contract_type">Type de contrat</label>
    <input id="contract_type" name="contract_type" type="text" value="{{ old('contract_type', $request->contract_type ?? '') }}" placeholder="Ex: CDI / CDD">
    @error('contract_type') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="planned_start_date">Date prévue de démarrage</label>
    <input id="planned_start_date" name="planned_start_date" type="date" value="{{ old('planned_start_date', $request->planned_start_date ?? '') }}">
    @error('planned_start_date') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="missions">Missions et tâches globales</label>
    <textarea id="missions" name="missions" rows="5" placeholder="Décrivez les missions et tâches globales">{{ old('missions', $request->missions ?? '') }}</textarea>
    @error('missions') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="personal_qualities">Qualités personnelles</label>
    <textarea id="personal_qualities" name="personal_qualities" rows="4" placeholder="Ex: Aisance relationnelle, rigueur, organisation...">{{ old('personal_qualities', $request->personal_qualities ?? '') }}</textarea>
    @error('personal_qualities') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="specific_knowledge">Connaissances spécifiques requises</label>
    <textarea id="specific_knowledge" name="specific_knowledge" rows="4" placeholder="Ex: SAP, Excel, maintenance, douane...">{{ old('specific_knowledge', $request->specific_knowledge ?? '') }}</textarea>
    @error('specific_knowledge') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="other_benefits">Autres avantages</label>
    <textarea id="other_benefits" name="other_benefits" rows="4" placeholder="Décrivez les avantages proposés">{{ old('other_benefits', $request->other_benefits ?? '') }}</textarea>
    @error('other_benefits') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label>Langues</label>
    <div class="checkbox-group">
      <label class="checkbox-item">
        <input type="checkbox" name="lang_ar" value="1" {{ old('lang_ar', $request->lang_ar ?? false) ? 'checked' : '' }}>
        <span>Arabe</span>
      </label>

      <label class="checkbox-item">
        <input type="checkbox" name="lang_fr" value="1" {{ old('lang_fr', $request->lang_fr ?? false) ? 'checked' : '' }}>
        <span>Français</span>
      </label>

      <label class="checkbox-item">
        <input type="checkbox" name="lang_en" value="1" {{ old('lang_en', $request->lang_en ?? false) ? 'checked' : '' }}>
        <span>Anglais</span>
      </label>

      <label class="checkbox-item">
        <input type="checkbox" name="lang_es" value="1" {{ old('lang_es', $request->lang_es ?? false) ? 'checked' : '' }}>
        <span>Espagnol</span>
      </label>
    </div>

    @error('lang_ar') <div class="form-error">{{ $message }}</div> @enderror
    @error('lang_fr') <div class="form-error">{{ $message }}</div> @enderror
    @error('lang_en') <div class="form-error">{{ $message }}</div> @enderror
    @error('lang_es') <div class="form-error">{{ $message }}</div> @enderror
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const hiddenInput = document.getElementById('work_location');
  const tagBox = document.getElementById('locationTagBox');
  const input = document.getElementById('work_location_tag_input');
  const suggestions = document.getElementById('locationSuggestions');

  if (!hiddenInput || !tagBox || !input || !suggestions) return;

  const citySuggestions = [
    'Casablanca','Rabat','Tanger','Marrakech','Fès','Meknès','Agadir','Oujda','Kénitra',
    'Mohammedia','Salé','Temara','Bouskoura','Nouaceur','Berrechid','Settat','El Jadida',
    'Safi','Béni Mellal','Khouribga','Tétouan','Nador','Larache','Ksar El Kebir',
    'Laâyoune','Dakhla','Errachidia','Taza','Ouarzazate','Essaouira','Al Hoceima'
  ];

  let tags = [];

  function normalizeTag(value) {
    return String(value || '').trim().replace(/\s+/g, ' ');
  }

  function loadInitialTags() {
    const initial = hiddenInput.value || '';

    tags = initial
      .split(/[,;|\/]+/)
      .map(normalizeTag)
      .filter(Boolean)
      .filter((value, index, array) => array.findIndex(v => v.toLowerCase() === value.toLowerCase()) === index);

    renderTags();
  }

  function syncHiddenInput() {
    hiddenInput.value = tags.join(', ');
  }

  function addTag(value) {
    value = normalizeTag(value);

    if (!value) return;

    const exists = tags.some(tag => tag.toLowerCase() === value.toLowerCase());

    if (!exists) {
      tags.push(value);
      renderTags();
    }

    input.value = '';
    suggestions.style.display = 'none';
  }

  function removeTag(index) {
    tags.splice(index, 1);
    renderTags();
  }

  function renderTags() {
    tagBox.querySelectorAll('.tag-chip').forEach(el => el.remove());

    tags.forEach((tag, index) => {
      const chip = document.createElement('span');
      chip.className = 'tag-chip';
      chip.innerHTML = `
        <span>${escapeHtml(tag)}</span>
        <button type="button" class="tag-remove" data-index="${index}">×</button>
      `;

      tagBox.insertBefore(chip, input);
    });

    tagBox.querySelectorAll('.tag-remove').forEach(btn => {
      btn.addEventListener('click', function () {
        removeTag(Number(this.dataset.index));
      });
    });

    syncHiddenInput();
  }

  function showSuggestions(query) {
    query = normalizeTag(query).toLowerCase();

    if (!query) {
      suggestions.style.display = 'none';
      return;
    }

    const results = citySuggestions
      .filter(city => city.toLowerCase().includes(query))
      .filter(city => !tags.some(tag => tag.toLowerCase() === city.toLowerCase()))
      .slice(0, 8);

    if (!results.length) {
      suggestions.style.display = 'none';
      return;
    }

    suggestions.innerHTML = results.map(city => `
      <div class="tag-suggestion" data-value="${escapeHtml(city)}">${escapeHtml(city)}</div>
    `).join('');

    suggestions.style.display = 'block';

    suggestions.querySelectorAll('.tag-suggestion').forEach(item => {
      item.addEventListener('click', function () {
        addTag(this.dataset.value);
      });
    });
  }

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',' || e.key === ';') {
      e.preventDefault();
      addTag(input.value);
    }

    if (e.key === 'Backspace' && input.value === '' && tags.length) {
      tags.pop();
      renderTags();
    }
  });

  input.addEventListener('input', function () {
    showSuggestions(input.value);
  });

  input.addEventListener('blur', function () {
    setTimeout(() => {
      if (input.value.trim() !== '') {
        addTag(input.value);
      }
      suggestions.style.display = 'none';
    }, 150);
  });

  tagBox.addEventListener('click', function () {
    input.focus();
  });

  loadInitialTags();
});
</script>