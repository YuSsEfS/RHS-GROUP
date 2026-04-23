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
          <path d="M6 9l6 6 6-6"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"/>
        </svg>
      </span>
    </div>
    @error('job_offer_id') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="reference">Affaire N°</label>
    <input
      id="reference"
      name="reference"
      type="text"
      value="{{ old('reference', $request->reference ?? '') }}"
      placeholder="Ex: AFF-2026-001"
    >
    @error('reference') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="client_name">Client / Demandeur</label>
    <input
      id="client_name"
      name="client_name"
      type="text"
      value="{{ old('client_name', $request->client_name ?? '') }}"
      placeholder="Ex: RFM"
    >
    @error('client_name') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="request_date">Date demande</label>
    <input
      id="request_date"
      name="request_date"
      type="date"
      value="{{ old('request_date', $request->request_date ?? '') }}"
    >
    @error('request_date') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="position_title">Poste à pourvoir</label>
    <input
      id="position_title"
      name="position_title"
      type="text"
      value="{{ old('position_title', $request->position_title ?? '') }}"
      placeholder="Ex: Gestionnaire de produit"
      required
    >
    @error('position_title') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="work_location">Lieu de travail</label>
    <input
      id="work_location"
      name="work_location"
      type="text"
      value="{{ old('work_location', $request->work_location ?? '') }}"
      placeholder="Ex: Casablanca"
    >
    @error('work_location') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="recruitment_reason">Motif de recrutement</label>
    <input
      id="recruitment_reason"
      name="recruitment_reason"
      type="text"
      value="{{ old('recruitment_reason', $request->recruitment_reason ?? '') }}"
      placeholder="Ex: Remplacement"
    >
    @error('recruitment_reason') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="age">Âge</label>
    <input
      id="age"
      name="age"
      type="text"
      value="{{ old('age', $request->age ?? '') }}"
      placeholder="Ex: 25-35"
    >
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
          <path d="M6 9l6 6 6-6"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"/>
        </svg>
      </span>
    </div>
    @error('gender') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="education">Formation</label>
    <input
      id="education"
      name="education"
      type="text"
      value="{{ old('education', $request->education ?? '') }}"
      placeholder="Ex: Bac+3 en logistique"
    >
    @error('education') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="experience_years">Expérience professionnelle</label>
    <input
      id="experience_years"
      name="experience_years"
      type="text"
      value="{{ old('experience_years', $request->experience_years ?? '') }}"
      placeholder="Ex: 2 à 3 ans"
    >
    @error('experience_years') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="availability">Disponibilité</label>
    <input
      id="availability"
      name="availability"
      type="text"
      value="{{ old('availability', $request->availability ?? '') }}"
      placeholder="Ex: ASAP"
    >
    @error('availability') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="other_language">Autre langue</label>
    <input
      id="other_language"
      name="other_language"
      type="text"
      value="{{ old('other_language', $request->other_language ?? '') }}"
      placeholder="Ex: Espagnol"
    >
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
          <path d="M6 9l6 6 6-6"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"/>
        </svg>
      </span>
    </div>
    @error('budget_type') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="monthly_salary">Rémunération mensuelle</label>
    <input
      id="monthly_salary"
      name="monthly_salary"
      type="text"
      value="{{ old('monthly_salary', $request->monthly_salary ?? '') }}"
      placeholder="Ex: Négociable"
    >
    @error('monthly_salary') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="contract_type">Type de contrat</label>
    <input
      id="contract_type"
      name="contract_type"
      type="text"
      value="{{ old('contract_type', $request->contract_type ?? '') }}"
      placeholder="Ex: CDI / CDD"
    >
    @error('contract_type') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field">
    <label for="planned_start_date">Date prévue de démarrage</label>
    <input
      id="planned_start_date"
      name="planned_start_date"
      type="date"
      value="{{ old('planned_start_date', $request->planned_start_date ?? '') }}"
    >
    @error('planned_start_date') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="missions">Missions et tâches globales</label>
    <textarea
      id="missions"
      name="missions"
      rows="5"
      placeholder="Décrivez les missions et tâches globales"
    >{{ old('missions', $request->missions ?? '') }}</textarea>
    @error('missions') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="personal_qualities">Qualités personnelles</label>
    <textarea
      id="personal_qualities"
      name="personal_qualities"
      rows="4"
      placeholder="Ex: Aisance relationnelle, rigueur, organisation..."
    >{{ old('personal_qualities', $request->personal_qualities ?? '') }}</textarea>
    @error('personal_qualities') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="specific_knowledge">Connaissances spécifiques requises</label>
    <textarea
      id="specific_knowledge"
      name="specific_knowledge"
      rows="4"
      placeholder="Ex: Bonne pratique de SAP, bonne maîtrise du français..."
    >{{ old('specific_knowledge', $request->specific_knowledge ?? '') }}</textarea>
    @error('specific_knowledge') <div class="form-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-field full">
    <label for="other_benefits">Autres avantages</label>
    <textarea
      id="other_benefits"
      name="other_benefits"
      rows="4"
      placeholder="Décrivez les avantages proposés"
    >{{ old('other_benefits', $request->other_benefits ?? '') }}</textarea>
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