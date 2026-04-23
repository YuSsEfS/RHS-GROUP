@extends('layouts.app')
@section('title','Postuler – RHS GROUP')

@section('content')
@php
  $isSpontaneous = request('type') === 'spontaneous';
@endphp

<div class="apply-page">

   

    {{-- ================= HERO ================= --}}
    <section class="apply-hero">
        <div class="container">
            <div class="apply-hero-content">

                <p class="apply-eyebrow" data-cms-key="apply.hero.eyebrow">CANDIDATURE</p>

                <h1 class="apply-title" data-cms-key="apply.hero.title">
                    Postulez et donnez<br>
                    un nouvel <span data-cms-key="apply.hero.title_span">élan</span> à votre carrière
                </h1>

                <p class="apply-subtitle" data-cms-key="apply.hero.subtitle">
                    RHS GROUP accompagne les talents vers des opportunités fiables,
                    durables et adaptées à leurs compétences.
                </p>

                {{-- Selected offer badge (dynamic title stays dynamic) --}}
              @if($isSpontaneous)
  <div class="apply-offer-badge">
    <span>Candidature :</span>
    <strong>Spontanée</strong>
  </div>
@elseif(isset($offer) && $offer)
  <div class="apply-offer-badge">
    <span data-cms-key="apply.hero.badge_prefix">Offre sélectionnée :</span>
    <strong>{{ $offer->title }}</strong>
  </div>
@endif

            </div>
        </div>
    </section>
     {{-- ================= SUCCESS MESSAGE ================= --}}
    @if(session('success'))
        <div class="container" style="padding-top:20px;">
            <div class="apply-alert-success">
                {{ session('success') }}
            </div>
        </div>
    @endif
    {{-- ================= FORM SECTION ================= --}}
    <section class="apply-form-section">
        <div class="container">
            <div class="apply-grid">

                {{-- ================= FORM ================= --}}
                <form class="apply-card"
                      action="{{ route('apply.store') }}"
                      method="POST"
                      enctype="multipart/form-data">

                    @csrf

                    <h2 data-cms-key="apply.form.title">Déposer ma candidature</h2>

                    <p class="apply-hint" data-cms-key="apply.form.hint">
                        Tous les champs marqués * sont obligatoires
                    </p>

                    {{-- Hidden offer id (IMPORTANT) --}}
                   <input type="hidden" name="job_offer_id"
       value="{{ $isSpontaneous ? '' : ($offer->id ?? request('offer')) }}">
<input type="hidden" name="type"
       value="{{ $isSpontaneous ? 'spontaneous' : '' }}">


                    {{-- ================= ERRORS ================= --}}
                    @if ($errors->any())
                        <div class="apply-alert-error">
                            <strong data-cms-key="apply.form.error_title">Erreur :</strong>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ================= FIELDS (LOCKED) ================= --}}
                    <div class="form-group">
                        <label data-cms-key="apply.form.full_name_label">Nom complet *</label>
                        <input type="text"
                               name="full_name"
                               value="{{ old('full_name') }}"
                               placeholder="Votre nom complet"
                               required>
                    </div>

                    <div class="form-group">
                        <label data-cms-key="apply.form.email_label">Email *</label>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="exemple@email.com"
                               required>
                    </div>
                    <div class="form-group">
    <label>Ville *</label>
    <input type="text" name="city" value="{{ old('city') }}" placeholder="Votre ville" required>
</div>

                    <div class="form-group">
                        <label data-cms-key="apply.form.phone_label">Téléphone *</label>
                        <input type="tel"
                               name="phone"
                               value="{{ old('phone') }}"
                               placeholder="+212..."
                               required>
                    </div>

                    <div class="form-group">
                        <label data-cms-key="apply.form.position_label">Poste recherché</label>
                        <input type="text"
       name="position"
       value="{{ old('position', $isSpontaneous ? 'Candidature spontanée' : ($offer->title ?? '')) }}"
       placeholder="Ex : Responsable RH">

                    </div>

                    <div class="form-group">
    <label data-cms-key="apply.form.cv_label">Votre CV (PDF/DOC) *</label>
    
    <label class="file-upload-label">
        Choisir un fichier
        <span class="filename">Aucun fichier choisi</span>
        <input type="file" name="cv" required>
    </label>
</div>

<div class="form-group">
    <label data-cms-key="apply.form.letter_label">Lettre de motivation (optionnel)</label>
    
    <label class="file-upload-label">
        Choisir un fichier
        <span class="filename">Aucun fichier choisi</span>
        <input type="file" name="letter">
    </label>
</div>


                    <div class="form-group">
                        <label data-cms-key="apply.form.message_label">Message</label>
                        <textarea name="message"
                                  rows="4"
                                  placeholder="Parlez-nous brièvement de votre profil…">{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary full" data-cms-key="apply.form.submit">
                        Envoyer ma candidature
                    </button>
                </form>

                {{-- ================= INFO (editable) ================= --}}
                <div class="apply-info">
                    <h3 data-cms-key="apply.info.title">Pourquoi postuler chez RHS GROUP ?</h3>

                    <ul class="apply-benefits">
                        <li data-cms-key="apply.info.b1">✔ Opportunités sérieuses et vérifiées</li>
                        <li data-cms-key="apply.info.b2">✔ Traitement confidentiel de votre dossier</li>
                        <li data-cms-key="apply.info.b3">✔ Réseau national d’entreprises partenaires</li>
                        <li data-cms-key="apply.info.b4">✔ Accompagnement personnalisé</li>
                    </ul>

                    <div class="apply-note" data-cms-key="apply.info.note">
                        Votre candidature sera étudiée avec attention par nos équipes RH.
                    </div>
                </div>

            </div>
        </div>
    </section>

</div>
<script>
    document.querySelectorAll('.file-upload-label input[type="file"]').forEach(input => {
    input.addEventListener('change', e => {
        const fileName = e.target.files[0]?.name || 'Aucun fichier choisi';
        e.target.parentElement.querySelector('.filename').textContent = fileName;
    });
});
</script>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/apply.css') }}">
@endpush
