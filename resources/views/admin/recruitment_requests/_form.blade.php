@php
  $requestSuggestions = config('recruitment_suggestions', []);
  $citySuggestions = $requestSuggestions['work_location'] ?? [];
  $missionSuggestions = $requestSuggestions['missions'] ?? [];
  $qualitySuggestions = $requestSuggestions['personal_qualities'] ?? [];
  $knowledgeSuggestions = $requestSuggestions['specific_knowledge'] ?? [];
  $benefitSuggestions = $requestSuggestions['other_benefits'] ?? [];
  $safeDateValue = function ($value): string {
      if ($value instanceof \Carbon\CarbonInterface) {
          return $value->format('Y-m-d');
      }

      $value = trim((string) $value);

      if ($value === '') {
          return '';
      }

      if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
          return $value;
      }

      if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $value, $match)) {
          return sprintf('%04d-%02d-%02d', (int) $match[3], (int) $match[2], (int) $match[1]);
      }

      return '';
  };
  $dateLabel = function (string $value): string {
      if ($value === '') {
          return 'Choisir une date';
      }

      [$year, $month, $day] = array_map('intval', explode('-', $value));

      return sprintf('%02d/%02d/%04d', $day, $month, $year);
  };
@endphp

@foreach($requestSuggestions as $suggestionField => $suggestionValues)
  <datalist id="rhs-suggestions-{{ $suggestionField }}">
    @foreach($suggestionValues as $suggestionValue)
      <option value="{{ $suggestionValue }}"></option>
    @endforeach
  </datalist>
@endforeach

<div class="form-grid rhs-recruitment-form-grid">
  @php
    $selectedOfferIds = collect(old('job_offer_ids', old('job_offer_id', $request->job_offer_id ?? null) ? [old('job_offer_id', $request->job_offer_id ?? null)] : []))->map(fn($id) => (string) $id)->all();
    $selectedFolderIds = collect(old('cv_folder_ids', old('cv_folder_id', $request->cv_folder_id ?? null) ? [old('cv_folder_id', $request->cv_folder_id ?? null)] : []))->map(fn($id) => (string) $id)->all();
  @endphp

  <div class="form-field">
    <label>Offres liees</label>
    <div class="rhs-multi-select" data-rhs-multi-select>
      <button type="button" class="rhs-multi-trigger" data-rhs-multi-trigger>
        <span data-rhs-multi-label>{{ count($selectedOfferIds) ? count($selectedOfferIds).' offre(s) selectionnee(s)' : 'Selectionner une ou plusieurs offres' }}</span>
        <span class="rhs-picker-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </button>
      <div class="rhs-multi-panel" data-rhs-multi-panel hidden>
        <input type="search" class="rhs-multi-search" placeholder="Rechercher une offre..." data-rhs-multi-search>
        <div class="rhs-multi-options">
          @foreach(($offers ?? collect()) as $offer)
            <label class="rhs-multi-option" data-rhs-multi-option>
              <input type="checkbox" name="job_offer_ids[]" value="{{ $offer->id }}" @checked(in_array((string) $offer->id, $selectedOfferIds, true))>
              <span>{{ $offer->title }}</span>
            </label>
          @endforeach
        </div>
      </div>
    </div>
    @error('job_offer_ids') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label>Dossiers CV Bank</label>
    <div class="rhs-multi-select" data-rhs-multi-select>
      <button type="button" class="rhs-multi-trigger" data-rhs-multi-trigger>
        <span data-rhs-multi-label>{{ count($selectedFolderIds) ? count($selectedFolderIds).' dossier(s) selectionne(s)' : 'Tous les dossiers ou selection multiple' }}</span>
        <span class="rhs-picker-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </button>
      <div class="rhs-multi-panel" data-rhs-multi-panel hidden>
        <input type="search" class="rhs-multi-search" placeholder="Rechercher un dossier..." data-rhs-multi-search>
        <div class="rhs-multi-options">
          @foreach(($folders ?? collect()) as $folder)
            <label class="rhs-multi-option" data-rhs-multi-option>
              <input type="checkbox" name="cv_folder_ids[]" value="{{ $folder->id }}" @checked(in_array((string) $folder->id, $selectedFolderIds, true))>
              <span>{{ $folder->name }}</span>
            </label>
          @endforeach
        </div>
      </div>
    </div>
    <div class="form-help">Si plusieurs dossiers/offres sont selectionnes, un matching sera lance par combinaison.</div>
    @error('cv_folder_ids') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field legacy-single-match-field">
    <label for="job_offer_id">Offre liée</label>
    <div class="select-wrapper">
      <select id="job_offer_id" name="job_offer_id_legacy" class="form-select" disabled>
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

  <div class="form-field legacy-single-match-field">
    <label for="cv_folder_id">Dossier CV Bank</label>
    <div class="select-wrapper">
      <select id="cv_folder_id" name="cv_folder_id_legacy" class="form-select" disabled>
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
    <label for="logo">Logo / image de la demande</label>
    <label class="rhs-file-card" for="logo">
      <span class="rhs-file-card-icon">+</span>
      <span>
        <strong>Ajouter une image</strong>
        <small>JPG, PNG ou WEBP. Le logo apparaitra sur la demande.</small>
      </span>
    </label>
    <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="rhs-file-card-input">
    <div class="form-help">Optionnel. Formats acceptes : JPG, PNG, WEBP, 2 Mo max.</div>
    @if(!empty($request?->logo_url))
      <div class="form-help">Image actuelle disponible.</div>
    @endif
    @error('logo') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="request_date">Date demande</label>
    @php($requestDateValue = $safeDateValue(old('request_date', $request->request_date ?? '')))
    <div class="rhs-date-picker rhs-form-date-picker" data-rhs-date-picker>
      <input id="request_date" type="hidden" name="request_date" value="{{ $requestDateValue }}" data-rhs-date-value>
      <button type="button" class="rhs-date-trigger" data-rhs-date-trigger>
        <span data-rhs-date-label>{{ $dateLabel($requestDateValue) }}</span>
        <span class="rhs-picker-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M8 2v4M16 2v4M4 10h16M6 4h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </button>
      <div class="rhs-date-popover" data-rhs-date-popover hidden>
        <div class="rhs-date-head">
          <button type="button" data-rhs-date-prev aria-label="Mois precedent">
            <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <strong data-rhs-date-month></strong>
          <button type="button" data-rhs-date-next aria-label="Mois suivant">
            <svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
        <div class="rhs-date-weekdays">
          <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
        </div>
        <div class="rhs-date-grid" data-rhs-date-grid></div>
      </div>
    </div>
    @error('request_date') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="position_title">Poste à pourvoir</label>
    <input id="position_title" name="position_title" type="text" value="{{ old('position_title', $request->position_title ?? '') }}" placeholder="Ex: Gestionnaire de produit" required>
    @error('position_title') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="work_location_tag_input">Lieu de travail</label>

    <div class="tag-field-relative js-tag-field" data-suggestions='@json($citySuggestions)'>
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

  <div class="form-field legacy-gender-field">
    <label for="gender">Sexe</label>
    <div class="select-wrapper">
      <select id="gender" name="gender_legacy" class="form-select" disabled>
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
    @php($plannedStartDateValue = $safeDateValue(old('planned_start_date', $request->planned_start_date ?? '')))
    <div class="rhs-date-picker rhs-form-date-picker" data-rhs-date-picker>
      <input id="planned_start_date" type="hidden" name="planned_start_date" value="{{ $plannedStartDateValue }}" data-rhs-date-value>
      <button type="button" class="rhs-date-trigger" data-rhs-date-trigger>
        <span data-rhs-date-label>{{ $dateLabel($plannedStartDateValue) }}</span>
        <span class="rhs-picker-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M8 2v4M16 2v4M4 10h16M6 4h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </button>
      <div class="rhs-date-popover" data-rhs-date-popover hidden>
        <div class="rhs-date-head">
          <button type="button" data-rhs-date-prev aria-label="Mois precedent">
            <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <strong data-rhs-date-month></strong>
          <button type="button" data-rhs-date-next aria-label="Mois suivant">
            <svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
        <div class="rhs-date-weekdays">
          <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
        </div>
        <div class="rhs-date-grid" data-rhs-date-grid></div>
      </div>
    </div>
    @error('planned_start_date') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="missions">Missions et tâches globales</label>
    <textarea id="missions" name="missions" rows="5" placeholder="Décrivez les missions et tâches globales">{{ old('missions', $request->missions ?? '') }}</textarea>
    @error('missions') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="personal_qualities">Qualités personnelles</label>
    <div class="tag-field-relative js-tag-field" data-suggestions='@json($qualitySuggestions)'>
      <input id="personal_qualities" name="personal_qualities" type="hidden" value="{{ old('personal_qualities', $request->personal_qualities ?? '') }}">
      <div class="tag-input-wrap">
        <input type="text" class="tag-input" placeholder="Ajouter une qualite puis Entree">
      </div>
      <div class="tag-suggestions"></div>
    </div>
    @error('personal_qualities') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="candidate_count">Nombre de candidats recherches</label>
    <input id="candidate_count" name="candidate_count" type="number" min="1" max="1000" value="{{ old('candidate_count', $request->candidate_count ?? '') }}" placeholder="Ex: 3" required>
    <div class="form-help">Indiquez le nombre de profils souhaites pour cette demande.</div>
    @error('candidate_count') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="specific_knowledge">Connaissances spécifiques requises</label>
    <div class="tag-field-relative js-tag-field" data-suggestions='@json($knowledgeSuggestions)'>
      <input id="specific_knowledge" name="specific_knowledge" type="hidden" value="{{ old('specific_knowledge', $request->specific_knowledge ?? '') }}">
      <div class="tag-input-wrap">
        <input type="text" class="tag-input" placeholder="Ajouter une connaissance puis Entree">
      </div>
      <div class="tag-suggestions"></div>
    </div>
    @error('specific_knowledge') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="other_benefits">Autres avantages</label>
    <div class="tag-field-relative js-tag-field" data-suggestions='@json($benefitSuggestions)'>
      <input id="other_benefits" name="other_benefits" type="hidden" value="{{ old('other_benefits', $request->other_benefits ?? '') }}">
      <div class="tag-input-wrap">
        <input type="text" class="tag-input" placeholder="Ajouter un avantage puis Entree">
      </div>
      <div class="tag-suggestions"></div>
    </div>
    @error('other_benefits') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label>Langues</label>
    <div class="checkbox-group language-checkbox-group">
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

@once
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const pad = (value) => String(value).padStart(2, '0');
        const monthLabel = (date) => date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
        const dateLabel = (value) => {
          if (!value) return 'Choisir une date';
          const [year, month, day] = value.split('-').map(Number);
          return `${pad(day)}/${pad(month)}/${year}`;
        };

        document.querySelectorAll('[data-rhs-date-picker]').forEach((picker) => {
          if (picker.dataset.rhsDateReady === '1') return;
          picker.dataset.rhsDateReady = '1';

          const hidden = picker.querySelector('[data-rhs-date-value]');
          const trigger = picker.querySelector('[data-rhs-date-trigger]');
          const label = picker.querySelector('[data-rhs-date-label]');
          const popover = picker.querySelector('[data-rhs-date-popover]');
          const monthNode = picker.querySelector('[data-rhs-date-month]');
          const grid = picker.querySelector('[data-rhs-date-grid]');

          if (!hidden || !trigger || !label || !popover || !monthNode || !grid) return;

          const selectedParts = hidden.value ? hidden.value.split('-').map(Number) : null;
          let cursor = selectedParts ? new Date(selectedParts[0], selectedParts[1] - 1, 1) : new Date();

          const closeOthers = () => {
            document.querySelectorAll('[data-rhs-date-popover], [data-rhs-time-popover]').forEach((node) => {
              if (!picker.contains(node)) node.hidden = true;
            });
          };

          const render = () => {
            const year = cursor.getFullYear();
            const month = cursor.getMonth();
            const firstDay = new Date(year, month, 1);
            const offset = (firstDay.getDay() + 6) % 7;
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const todayValue = new Date().toISOString().slice(0, 10);

            monthNode.textContent = monthLabel(cursor);
            grid.innerHTML = '';

            for (let i = 0; i < offset; i++) {
              grid.appendChild(document.createElement('span'));
            }

            for (let day = 1; day <= daysInMonth; day++) {
              const value = `${year}-${pad(month + 1)}-${pad(day)}`;
              const button = document.createElement('button');
              button.type = 'button';
              button.textContent = day;
              button.dataset.date = value;
              if (value === hidden.value) button.classList.add('is-selected');
              if (value === todayValue) button.classList.add('is-today');
              button.addEventListener('click', () => {
                hidden.value = value;
                label.textContent = dateLabel(value);
                popover.hidden = true;
              });
              grid.appendChild(button);
            }
          };

          trigger.addEventListener('click', () => {
            closeOthers();
            popover.hidden = !popover.hidden;
            render();
          });

          picker.querySelector('[data-rhs-date-prev]')?.addEventListener('click', () => {
            cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
            render();
          });

          picker.querySelector('[data-rhs-date-next]')?.addEventListener('click', () => {
            cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
            render();
          });

          label.textContent = dateLabel(hidden.value);
        });

        document.addEventListener('click', (event) => {
          if (event.target.closest('[data-rhs-date-picker], [data-rhs-time-picker]')) return;
          document.querySelectorAll('[data-rhs-date-popover], [data-rhs-time-popover]').forEach((node) => {
            node.hidden = true;
          });
        });
      });
    </script>
  @endpush
@endonce

<script>
document.addEventListener('DOMContentLoaded', function () {
  const requestSuggestions = @json($requestSuggestions);
  const suggestionFields = Object.keys(requestSuggestions);

  function normalizeSuggestion(value) {
    return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  }

  function escapeSuggestionHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  suggestionFields.forEach(function (fieldName) {
    document.querySelectorAll('input[name="' + fieldName + '"], textarea[name="' + fieldName + '"]').forEach(function (field) {
      if (field.type === 'hidden' || field.type === 'file' || field.type === 'date' || field.closest('.js-tag-field')) return;

      field.setAttribute('autocomplete', 'off');

      const fieldWrap = field.closest('.form-field') || field.parentElement;
      const panel = document.createElement('div');
      panel.className = 'rhs-field-suggestions';
      panel.hidden = true;
      fieldWrap.appendChild(panel);

      const values = Array.isArray(requestSuggestions[fieldName]) ? requestSuggestions[fieldName] : [];

      const close = function () {
        panel.hidden = true;
        panel.innerHTML = '';
      };

      const open = function () {
        const query = normalizeSuggestion(field.value);
        const matches = values
          .filter((item) => {
            const normalized = normalizeSuggestion(item);
            return query === '' || normalized.includes(query);
          })
          .slice(0, 8);

        if (!matches.length) {
          close();
          return;
        }

        panel.innerHTML = matches.map((item) => `
          <button type="button" class="rhs-field-suggestion" data-value="${escapeSuggestionHtml(item)}">
            <span>${escapeSuggestionHtml(item)}</span>
          </button>
        `).join('');
        panel.hidden = false;
      };

      field.addEventListener('focus', open);
      field.addEventListener('input', open);

      field.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' || panel.hidden) return;

        const first = panel.querySelector('.rhs-field-suggestion');
        if (!first) return;

        event.preventDefault();
        field.value = first.dataset.value || '';
        close();
      });

      panel.addEventListener('mousedown', function (event) {
        const option = event.target.closest('.rhs-field-suggestion');
        if (!option) return;

        event.preventDefault();
        field.value = option.dataset.value || '';
        close();
        field.focus();
      });

      document.addEventListener('click', function (event) {
        if (fieldWrap.contains(event.target)) return;
        close();
      });
    });
  });

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

  function isNoiseTag(value) {
    const normalized = normalizeTag(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    return ['', 'et', '&', 'and', 'ou', 'or'].includes(normalized);
  }

  function loadInitialTags() {
    const initial = hiddenInput.value || '';

    tags = initial
      .split(/\n+|[,;|\/]+/)
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

    if (!value || isNoiseTag(value)) return;

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
        <button type="button" class="tag-remove" data-index="${index}">&times;</button>
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
      item.addEventListener('mousedown', function (event) {
        event.preventDefault();
        addTag(this.dataset.value);
      });
    });
  }

  function findBestSuggestion(value) {
    const query = normalizeTag(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    return citySuggestions.find(city => city.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase() === query)
      || citySuggestions.find(city => city.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().startsWith(query))
      || citySuggestions.find(city => city.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().includes(query))
      || value;
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
      addTag(findBestSuggestion(input.value));
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
        addTag(findBestSuggestion(input.value));
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

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-rhs-multi-select]').forEach(function (multi) {
    if (multi.dataset.ready === '1') return;
    multi.dataset.ready = '1';

    const trigger = multi.querySelector('[data-rhs-multi-trigger]');
    const panel = multi.querySelector('[data-rhs-multi-panel]');
    const label = multi.querySelector('[data-rhs-multi-label]');
    const search = multi.querySelector('[data-rhs-multi-search]');
    const options = Array.from(multi.querySelectorAll('[data-rhs-multi-option]'));
    const baseLabel = label ? label.textContent.trim() : 'Selectionner';

    const escapeHtml = function (value) {
      return String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    };

    const refresh = function () {
      const checked = options.filter((option) => option.querySelector('input')?.checked);
      if (!label) return;

      if (!checked.length) {
        label.innerHTML = `<span class="rhs-multi-placeholder">${escapeHtml(baseLabel)}</span>`;
        return;
      }

      const visibleChips = checked.slice(0, 4).map((option) => {
        const text = option.querySelector('span')?.textContent?.trim() || option.textContent.trim();
        return `<span class="rhs-multi-chip">${escapeHtml(text)}</span>`;
      }).join('');
      const overflowChip = checked.length > 4 ? `<span class="rhs-multi-chip is-more">+${checked.length - 4}</span>` : '';

      label.innerHTML = `<span class="rhs-multi-chip-wrap">${visibleChips}${overflowChip}</span>`;
    };

    trigger?.addEventListener('click', function () {
      document.querySelectorAll('[data-rhs-multi-panel]').forEach((node) => {
        if (node !== panel) node.hidden = true;
      });
      panel.hidden = !panel.hidden;
      if (!panel.hidden) search?.focus();
    });

    const normalizeSearch = function (value) {
      return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    };

    search?.addEventListener('input', function () {
      const query = normalizeSearch(search.value);
      options.forEach(function (option) {
        const text = normalizeSearch(option.querySelector('span')?.textContent || option.textContent);
        option.hidden = query !== '' && !text.includes(query);
      });
    });

    panel?.addEventListener('click', function (event) {
      event.stopPropagation();
    });

    options.forEach(function (option) {
      option.querySelector('input')?.addEventListener('change', refresh);
    });

    refresh();
  });

  document.addEventListener('click', function (event) {
    if (event.target.closest('[data-rhs-multi-select]')) return;
    document.querySelectorAll('[data-rhs-multi-panel]').forEach((node) => node.hidden = true);
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  function normalizeTag(value) {
    return String(value || '').trim().replace(/\s+/g, ' ');
  }

  function normalizeForSearch(value) {
    return normalizeTag(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  }

  function isNoiseTag(value) {
    return ['', 'et', '&', 'and', 'ou', 'or'].includes(normalizeForSearch(value));
  }

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  const missionsTextarea = document.getElementById('missions');

  if (missionsTextarea && missionsTextarea.tagName === 'TEXTAREA') {
    const wrapper = document.createElement('div');
    wrapper.className = 'tag-field-relative js-tag-field';
    wrapper.dataset.suggestions = JSON.stringify(@json($missionSuggestions));
    wrapper.innerHTML = `
      <input id="missions" name="missions" type="hidden" value="${escapeHtml(missionsTextarea.value || '')}">
      <div class="tag-input-wrap">
        <input type="text" class="tag-input" placeholder="Ajouter une mission puis Entree">
      </div>
      <div class="tag-suggestions"></div>
    `;
    missionsTextarea.replaceWith(wrapper);
  }

  document.querySelectorAll('.js-tag-field').forEach(function (field) {
    const hiddenInput = field.querySelector('input[type="hidden"]');

    if (!hiddenInput || hiddenInput.id === 'work_location') {
      return;
    }

    const tagBox = field.querySelector('.tag-input-wrap');
    const input = field.querySelector('.tag-input');
    const suggestions = field.querySelector('.tag-suggestions');
    const suggestionList = JSON.parse(field.dataset.suggestions || '[]');
    let tags = (hiddenInput.value || '')
      .split(/\n+|[,;|\/]+/)
      .map(normalizeTag)
      .filter(Boolean)
      .filter((value, index, array) => array.findIndex((item) => normalizeForSearch(item) === normalizeForSearch(value)) === index);

    if (!tagBox || !input || !suggestions) {
      return;
    }

    function syncHiddenInput() {
      hiddenInput.value = tags.join("\n");
    }

    function closestSuggestion(value) {
      const query = normalizeForSearch(value);

      return suggestionList.find((item) => normalizeForSearch(item) === query)
        || suggestionList.find((item) => normalizeForSearch(item).startsWith(query))
        || suggestionList.find((item) => normalizeForSearch(item).includes(query))
        || value;
    }

    function renderTags() {
      tagBox.querySelectorAll('.tag-chip').forEach((el) => el.remove());

      tags.forEach((tag, index) => {
        const chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.innerHTML = `<span>${escapeHtml(tag)}</span><button type="button" class="tag-remove" data-index="${index}">&times;</button>`;
        tagBox.insertBefore(chip, input);
      });

      tagBox.querySelectorAll('.tag-remove').forEach((button) => {
        button.addEventListener('click', function () {
          tags.splice(Number(this.dataset.index), 1);
          renderTags();
        });
      });

      syncHiddenInput();
    }

    function addTag(value) {
      value = normalizeTag(value);

      if (!value || isNoiseTag(value)) {
        return;
      }

      if (!tags.some((tag) => normalizeForSearch(tag) === normalizeForSearch(value))) {
        tags.push(value);
        renderTags();
      }

      input.value = '';
      suggestions.style.display = 'none';
    }

    function showSuggestions(value) {
      const query = normalizeForSearch(value);

      if (!query) {
        suggestions.style.display = 'none';
        return;
      }

      const results = suggestionList
        .filter((item) => normalizeForSearch(item).includes(query))
        .filter((item) => !tags.some((tag) => normalizeForSearch(tag) === normalizeForSearch(item)))
        .slice(0, 8);

      if (!results.length) {
        suggestions.style.display = 'none';
        return;
      }

      suggestions.innerHTML = results.map((item) => `<div class="tag-suggestion" data-value="${escapeHtml(item)}">${escapeHtml(item)}</div>`).join('');
      suggestions.style.display = 'block';
      suggestions.querySelectorAll('.tag-suggestion').forEach((item) => {
        item.addEventListener('mousedown', function (event) {
          event.preventDefault();
          addTag(this.dataset.value);
        });
      });
    }

    input.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ',' || event.key === ';') {
        event.preventDefault();
        addTag(closestSuggestion(input.value));
      }

      if (event.key === 'Backspace' && input.value === '' && tags.length) {
        tags.pop();
        renderTags();
      }
    });

    input.addEventListener('input', function () {
      showSuggestions(input.value);
    });

    input.addEventListener('blur', function () {
      setTimeout(function () {
        if (input.value.trim() !== '') {
          addTag(closestSuggestion(input.value));
        }
        suggestions.style.display = 'none';
      }, 150);
    });

    tagBox.addEventListener('click', function () {
      input.focus();
    });

    renderTags();
  });
});
</script>
