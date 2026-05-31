@extends('dashboard.layouts.app')

@section('title', 'Nouvelle demande')
@section('brand', 'RHS Client')
@section('brand_sub', 'Portail recrutement')
@section('page_title', 'Nouvelle demande de recrutement')
@section('page_copy', 'Renseignez votre besoin de recrutement. Le suivi global de la demande restera visible depuis votre tableau de bord, sans aucun acces aux CV ou aux candidats.')

@section('sidebar')
    @include('client._sidebar')
@endsection

@section('top_badge')
    <span class="portal-badge">Creation securisee</span>
@endsection

@section('content')
    @php
        $requestSuggestions = config('recruitment_suggestions', []);
        $citySuggestions = $requestSuggestions['work_location'] ?? [];
        $qualitySuggestions = $requestSuggestions['personal_qualities'] ?? [];
        $knowledgeSuggestions = $requestSuggestions['specific_knowledge'] ?? [];
        $benefitSuggestions = $requestSuggestions['other_benefits'] ?? [];
    @endphp
    @foreach($requestSuggestions as $suggestionField => $suggestionValues)
        <datalist id="rhs-suggestions-{{ $suggestionField }}">
            @foreach($suggestionValues as $suggestionValue)
                <option value="{{ $suggestionValue }}"></option>
            @endforeach
        </datalist>
    @endforeach
    <section class="portal-card">
        <div class="portal-toolbar">
            <div>
                <h3 class="portal-title-tight">Formulaire de demande</h3>
                <p class="portal-copy portal-copy-tight">Les donnees saisies servent uniquement a qualifier votre besoin. Les details candidats resteront strictement reserves a RHS.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('client.recruitment-requests.store') }}" enctype="multipart/form-data" class="portal-form-grid client-request-form">
            @csrf
            <div class="full client-form-section-title">Informations generales</div>
            <div>
                <label for="reference">Reference interne</label>
                <input id="reference" name="reference" type="text" value="{{ old('reference') }}" placeholder="Ex: RHS-CLI-001">
            </div>
            <div>
                <label for="logo">Logo / image du besoin</label>
                <label class="rhs-file-card" for="logo">
                    <span class="rhs-file-card-icon">+</span>
                    <span>
                        <strong>Ajouter une image</strong>
                        <small>JPG, PNG ou WEBP</small>
                    </span>
                </label>
                <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="rhs-file-card-input">
                <small>Optionnel, visible uniquement par RHS.</small>
            </div>
            <div>
                <label for="position_title">Poste recherche</label>
                <input id="position_title" name="position_title" type="text" value="{{ old('position_title') }}" required placeholder="Ex: Responsable achats">
            </div>
            <div>
                <label for="candidate_count">Nombre de candidats recherches</label>
                <input id="candidate_count" name="candidate_count" type="number" min="1" max="1000" value="{{ old('candidate_count') }}" placeholder="Ex: 3" required>
            </div>
            <div>
                <label for="work_location">Lieu de travail</label>
                <div class="tag-field-relative js-tag-field" data-suggestions='@json($citySuggestions)'>
                    <input id="work_location" name="work_location" type="hidden" value="{{ old('work_location') }}">
                    <div class="tag-input-wrap">
                        <input type="text" class="tag-input" placeholder="Ajouter une ville puis Entree">
                    </div>
                    <div class="tag-suggestions"></div>
                </div>
            </div>
            <div>
                <label for="recruitment_reason">Motif de recrutement</label>
                <input id="recruitment_reason" name="recruitment_reason" type="text" value="{{ old('recruitment_reason') }}" placeholder="Ex: Remplacement, creation de poste">
            </div>
            <div class="full client-form-section-title">Profil recherche</div>
            <div>
                <label for="contract_type">Type de contrat</label>
                <input id="contract_type" name="contract_type" type="text" value="{{ old('contract_type') }}" placeholder="Ex: CDI">
            </div>
            <div>
                 <label for="education">Formation</label>
                <input id="education" name="education" type="text" value="{{ old('education') }}" placeholder="Ex: Bac+3 en logistique">
            </div>
            <div>
                <label for="experience_years">Experience souhaitee</label>
                <input id="experience_years" name="experience_years" type="text" value="{{ old('experience_years') }}" placeholder="Ex: 5 ans minimum">
            </div>
            <div>
                <label for="planned_start_date">Date souhaitee</label>
                <input id="planned_start_date" name="planned_start_date" type="date" value="{{ old('planned_start_date') }}">
            </div>
            <div>
                <label for="age">Age souhaite</label>
                <input id="age" name="age" type="text" value="{{ old('age') }}" placeholder="Ex: 25-35 / minimum 22">
            </div>
            <div>
                <label for="availability">Disponibilite</label>
                <input id="availability" name="availability" type="text" value="{{ old('availability') }}" placeholder="Ex: Immediate / 1 mois">
            </div>
            <div>
                <label for="monthly_salary">Remuneration mensuelle</label>
                <input id="monthly_salary" name="monthly_salary" type="text" value="{{ old('monthly_salary') }}" placeholder="Ex: A negocier">
            </div>
            <div>
                <label for="budget_type">Budget du poste</label>
                <input id="budget_type" name="budget_type" type="text" value="{{ old('budget_type') }}" placeholder="Ex: Poste budgete">
            </div>
            <div class="full">
                <label for="missions">Missions principales</label>
                <textarea id="missions" name="missions" rows="5" placeholder="Decrivez les principales missions du poste">{{ old('missions') }}</textarea>
            </div>
            <div class="full client-form-section-title">Competences et avantages</div>
            <div class="full">
                <label for="specific_knowledge">Competences et connaissances requises</label>
                <div class="tag-field-relative js-tag-field" data-suggestions='@json($knowledgeSuggestions)'>
                    <input id="specific_knowledge" name="specific_knowledge" type="hidden" value="{{ old('specific_knowledge') }}">
                    <div class="tag-input-wrap">
                        <input type="text" class="tag-input" placeholder="Ajouter une competence puis Entree">
                    </div>
                    <div class="tag-suggestions"></div>
                </div>
            </div>
            <div>
                <label for="other_language">Autre langue</label>
                <input id="other_language" name="other_language" type="text" value="{{ old('other_language') }}" placeholder="Ex: Espagnol, Italien">
            </div>
            <div>
                <label>Langues souhaitees</label>
                <div class="checkbox-group client-language-grid">
                    <label class="checkbox-item"><input type="checkbox" name="lang_ar" value="1" @checked(old('lang_ar'))><span>Arabe</span></label>
                    <label class="checkbox-item"><input type="checkbox" name="lang_fr" value="1" @checked(old('lang_fr'))><span>Francais</span></label>
                    <label class="checkbox-item"><input type="checkbox" name="lang_en" value="1" @checked(old('lang_en'))><span>Anglais</span></label>
                    <label class="checkbox-item"><input type="checkbox" name="lang_es" value="1" @checked(old('lang_es'))><span>Espagnol</span></label>
                </div>
            </div>
            <div class="full">
                <label for="personal_qualities">Qualites attendues</label>
                <div class="tag-field-relative js-tag-field" data-suggestions='@json($qualitySuggestions)'>
                    <input id="personal_qualities" name="personal_qualities" type="hidden" value="{{ old('personal_qualities') }}">
                    <div class="tag-input-wrap">
                        <input type="text" class="tag-input" placeholder="Ajouter une qualite puis Entree">
                    </div>
                    <div class="tag-suggestions"></div>
                </div>
            </div>
            <div class="full">
                <label for="other_benefits">Autres avantages</label>
                <div class="tag-field-relative js-tag-field" data-suggestions='@json($benefitSuggestions)'>
                    <input id="other_benefits" name="other_benefits" type="hidden" value="{{ old('other_benefits') }}">
                    <div class="tag-input-wrap">
                        <input type="text" class="tag-input" placeholder="Ajouter un avantage puis Entree">
                    </div>
                    <div class="tag-suggestions"></div>
                </div>
            </div>
            <div class="full portal-form-actions">
                <a href="{{ route('client.dashboard') }}" class="admin-btn admin-btn-ghost portal-btn-auto">Retour</a>
                <button type="submit" class="admin-btn admin-btn-primary portal-btn-auto">Envoyer la demande</button>
            </div>
        </form>
    </section>
    @include('partials.rhs-tag-fields')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const suggestionFields = @json(array_keys($requestSuggestions));

        suggestionFields.forEach(function (fieldName) {
            document.querySelectorAll('input[name="' + fieldName + '"], textarea[name="' + fieldName + '"]').forEach(function (field) {
                if (field.type === 'hidden' || field.type === 'file' || field.type === 'date') return;
                field.setAttribute('list', 'rhs-suggestions-' + fieldName);
                field.setAttribute('autocomplete', 'off');
            });
        });
    });
    </script>
@endsection
