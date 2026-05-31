@extends('layouts.app')
@section('title','Offres d’emploi – RHS GROUP')

@section('content')
<div class="jobs-page">

  {{-- ================= HERO ================= --}}
  <section class="jobs-hero">
    <div class="container jobs-hero-inner">
      <div class="jobs-hero-content">
        <p class="jobs-eyebrow" data-cms-key="jobs.hero.eyebrow">OPPORTUNITÉS DE CARRIÈRE</p>

        <h1 class="jobs-title" data-cms-key="jobs.hero.title">
          Trouvez l’emploi<br>
          qui vous <span data-cms-key="jobs.hero.title_span">ressemble</span>
        </h1>

        <p class="jobs-subtitle" data-cms-key="jobs.hero.subtitle">
          RHS GROUP accompagne les talents vers des opportunités durables, adaptées à leurs compétences.
        </p>

        {{-- Optional: small scroll hint --}}
        <div class="jobs-hero-actions">
  <a href="#jobs-list" class="jobs-hero-cta">
    Voir les offres
    <span class="jobs-hero-cta-icon">↓</span>
  </a>

  <a href="{{ route('jobs', ['apply' => 'spontaneous']) }}#jobs-list"
     class="jobs-hero-cta jobs-hero-cta--secondary">
    Candidature spontanée
    <span class="jobs-hero-cta-icon">✉</span>
  </a>
</div>

      </div>
    </div>
  </section>

  {{-- ================= LIST ================= --}}
  <section class="jobs-list" id="jobs-list">
    <div class="container">
    {{-- ================= FILTERS ================= --}}
<form class="jobs-filters" method="GET" action="{{ url()->current() }}" autocomplete="off">
  <div class="jobs-filters-row">

    {{-- Search --}}
  <div class="jobs-filter jobs-filter-search">
  <label class="sr-only" for="q">Rechercher</label>

  <div class="jobs-search-wrap">
    <input
      id="q"
      type="text"
      name="q"
      value="{{ request('q') }}"
      placeholder="Rechercher (titre, entreprise)..."
      autocomplete="off"
    >
    <div id="jobsSuggest" class="jobs-suggest" hidden></div>
  </div>
</div>


    {{-- Location --}}
    <div class="jobs-filter">
      <label class="sr-only" for="location">Lieu</label>
      <div class="jobs-select" data-jobs-select>
        <select id="location" name="location">
          <option value="">Tous les lieux</option>
          @foreach(($locations ?? collect()) as $loc)
            <option value="{{ $loc }}" {{ request('location') === $loc ? 'selected' : '' }}>
              {{ $loc }}
            </option>
          @endforeach
        </select>
        <button type="button" class="jobs-select-trigger" data-jobs-select-trigger aria-haspopup="listbox" aria-expanded="false">
          <span data-jobs-select-label></span>
          <span class="jobs-select-arrow" aria-hidden="true">⌄</span>
        </button>
        <div class="jobs-select-menu" data-jobs-select-menu role="listbox" hidden></div>
      </div>
    </div>

    {{-- Contract --}}
    <div class="jobs-filter">
      <label class="sr-only" for="contract">Contrat</label>
      <div class="jobs-select" data-jobs-select>
        <select id="contract" name="contract">
          <option value="">Tous contrats</option>
          @foreach(($contracts ?? collect()) as $c)
            <option value="{{ $c }}" {{ request('contract') === $c ? 'selected' : '' }}>
              {{ $c }}
            </option>
          @endforeach
        </select>
        <button type="button" class="jobs-select-trigger" data-jobs-select-trigger aria-haspopup="listbox" aria-expanded="false">
          <span data-jobs-select-label></span>
          <span class="jobs-select-arrow" aria-hidden="true">⌄</span>
        </button>
        <div class="jobs-select-menu" data-jobs-select-menu role="listbox" hidden></div>
      </div>
    </div>

    {{-- Sector --}}
    <div class="jobs-filter">
      <label class="sr-only" for="sector">Secteur</label>
      <div class="jobs-select" data-jobs-select>
        <select id="sector" name="sector">
          <option value="">Tous secteurs</option>
          @foreach(($sectors ?? collect()) as $s)
            <option value="{{ $s }}" {{ request('sector') === $s ? 'selected' : '' }}>
              {{ $s }}
            </option>
          @endforeach
        </select>
        <button type="button" class="jobs-select-trigger" data-jobs-select-trigger aria-haspopup="listbox" aria-expanded="false">
          <span data-jobs-select-label></span>
          <span class="jobs-select-arrow" aria-hidden="true">⌄</span>
        </button>
        <div class="jobs-select-menu" data-jobs-select-menu role="listbox" hidden></div>
      </div>
    </div>

    {{-- Sort --}}
    <div class="jobs-filter">
      <label class="sr-only" for="sort">Tri</label>
      <div class="jobs-select" data-jobs-select>
        <select id="sort" name="sort">
          <option value="new" {{ request('sort','new') === 'new' ? 'selected' : '' }}>Plus récentes</option>
          <option value="old" {{ request('sort') === 'old' ? 'selected' : '' }}>Plus anciennes</option>
        </select>
        <button type="button" class="jobs-select-trigger" data-jobs-select-trigger aria-haspopup="listbox" aria-expanded="false">
          <span data-jobs-select-label></span>
          <span class="jobs-select-arrow" aria-hidden="true">⌄</span>
        </button>
        <div class="jobs-select-menu" data-jobs-select-menu role="listbox" hidden></div>
      </div>
    </div>

    <div class="jobs-filter-actions">
      <button type="submit" class="jobs-filter-btn" data-cms-key="jobs.filters.submit">Filtrer</button>
      <a class="jobs-filter-reset" href="{{ url()->current() }}" data-cms-key="jobs.filters.reset">Réinitialiser</a>
    </div>

  </div>
</form>

      <div class="jobs-grid">
        @forelse($offers as $offer)
          <article class="job-card" data-rhs-job-card data-offer-id="{{ $offer->id }}">

            {{-- Top --}}
            <div class="job-card-top">
              <div class="job-main">
                <div class="job-title-row">
                  <h3 class="job-title">{{ $offer->title }}</h3>

                  {{-- optional: tiny badge (if sector exists) --}}
                  @if(!empty($offer->sector))
                    <span class="job-badge">{{ $offer->sector }}</span>
                  @endif
                </div>

                <p class="job-company">{{ $offer->company ?: 'RHS GROUP' }}</p>

                @if(!empty($offer->excerpt))
                  <p class="job-desc">{{ $offer->excerpt }}</p>
                @endif
              </div>

              {{-- Meta pills --}}
              <div class="job-meta">
                <span class="job-pill">
                  <span class="job-pill-dot"></span>
                  <span class="job-pill-text">{{ $offer->location ?: '—' }}</span>
                </span>

                <span class="job-pill">
                  <span class="job-pill-dot"></span>
                  <span class="job-pill-text">{{ $offer->contract_type ?: '—' }}</span>
                </span>

                @if(!empty($offer->sector))
                  <span class="job-pill is-soft">
                    <span class="job-pill-dot"></span>
                    <span class="job-pill-text">{{ $offer->sector }}</span>
                  </span>
                @endif
              </div>
            </div>

            {{-- Bottom --}}
            <div class="job-card-bottom">
              <div class="job-date">
                <span class="job-date-label" data-cms-key="jobs.card.published_label">Publié :</span>
                <span class="job-date-value">
{{ optional($offer->published_at)->format('d/m/Y') ?: '—' }}
                </span>
              </div>

              <button type="button"
                 class="job-btn"
                 data-rhs-open-offer
                 data-offer-id="{{ $offer->id }}"
                 data-cms-key="jobs.card.btn">
                Voir l’offre
                <span class="job-btn-icon">→</span>
              </button>
            </div>

          </article>
        @empty
          <div class="jobs-empty">
            <div class="jobs-empty-icon">📭</div>
            <h3 data-cms-key="jobs.empty.title">Aucune offre disponible</h3>
            <p data-cms-key="jobs.empty.desc">Revenez bientôt, de nouvelles opportunités seront publiées.</p>
          </div>
        @endforelse
      </div>

      @if(method_exists($offers, 'links'))
        <div class="jobs-pagination">
          {{ $offers->links() }}
        </div>
      @endif

    </div>
  </section>

  <div class="rhs-modal" id="jobOfferModal" aria-hidden="true">
    <div class="rhs-modal-backdrop" data-rhs-close-modal></div>
    <section class="rhs-modal-panel rhs-offer-modal" role="dialog" aria-modal="true" aria-labelledby="jobModalTitle">
      <button type="button" class="rhs-modal-close" data-rhs-close-modal aria-label="Fermer">&times;</button>

      <div class="rhs-modal-head">
        <p class="section-eyebrow" data-cms-key="jobs.modal.offer.eyebrow">Offre d'emploi</p>
        <h2 id="jobModalTitle"></h2>
        <div class="job-modal-meta" id="jobModalMeta"></div>
      </div>

      <div class="job-modal-image-wrap" id="jobModalImageWrap" hidden>
        <img id="jobModalImage" src="" alt="">
      </div>

      <div class="job-modal-body">
        <article>
          <h3 data-cms-key="jobs.modal.offer.description_title">Description</h3>
          <div id="jobModalDescription"></div>
        </article>
        <article id="jobModalMissionsWrap" hidden>
          <h3 data-cms-key="jobs.modal.offer.missions_title">Missions</h3>
          <div id="jobModalMissions"></div>
        </article>
        <article id="jobModalRequirementsWrap" hidden>
          <h3 data-cms-key="jobs.modal.offer.profile_title">Profil recherché</h3>
          <div id="jobModalRequirements"></div>
        </article>
      </div>

      <div class="rhs-modal-actions">
        <button type="button" class="btn-primary" data-rhs-open-apply>
          <span data-cms-key="jobs.modal.offer.apply_btn">Postuler</span> <span>→</span>
        </button>
        <button type="button" class="btn-outline-red" data-rhs-close-modal>
          Fermer
        </button>
      </div>
    </section>
  </div>

  <div class="rhs-modal" id="jobApplyModal" aria-hidden="true">
    <div class="rhs-modal-backdrop" data-rhs-close-modal></div>
    <section class="rhs-modal-panel rhs-apply-modal" role="dialog" aria-modal="true" aria-labelledby="applyModalTitle">
      <button type="button" class="rhs-modal-close" data-rhs-close-modal aria-label="Fermer">&times;</button>

      <div class="rhs-modal-head">
        <p class="section-eyebrow" data-cms-key="jobs.modal.apply.eyebrow">Candidature</p>
        <h2 id="applyModalTitle" data-cms-key="jobs.modal.apply.title">Postuler à cette offre</h2>
        <p id="applyModalSubtitle" data-cms-key="jobs.modal.apply.subtitle">Complétez les étapes pour transmettre votre candidature à RHS GROUP.</p>
      </div>

      <form class="rhs-apply-steps" method="POST" action="{{ route('apply.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="job_offer_id" id="applyJobOfferId">
        <input type="hidden" name="type" id="applyType" value="job">

        <div class="apply-stepper" aria-label="Etapes de candidature">
          <button type="button" class="apply-step-dot is-active" data-step-indicator="0"><span>1</span><b data-cms-key="jobs.modal.apply.step_profile">Profil</b></button>
          <button type="button" class="apply-step-dot" data-step-indicator="1"><span>2</span><b data-cms-key="jobs.modal.apply.step_documents">Documents</b></button>
          <button type="button" class="apply-step-dot" data-step-indicator="2"><span>3</span><b data-cms-key="jobs.modal.apply.step_validation">Validation</b></button>
        </div>

        <div class="apply-step is-active" data-apply-step="0">
          <div class="modal-form-grid">
            <label><span data-cms-key="jobs.modal.apply.full_name_label">Nom complet</span>
              <input type="text" name="full_name" required placeholder="Votre nom">
            </label>
            <label><span data-cms-key="jobs.modal.apply.email_label">Email</span>
              <input type="email" name="email" required placeholder="ex: nom@email.com">
            </label>
            <label><span data-cms-key="jobs.modal.apply.phone_label">Téléphone</span>
              <input type="tel" name="phone" placeholder="ex: 06xx xx xx xx">
            </label>
            <label><span data-cms-key="jobs.modal.apply.city_label">Ville</span>
              <input type="text" name="city" placeholder="Votre ville">
            </label>
          </div>
        </div>

        <div class="apply-step" data-apply-step="1">
          <div class="modal-form-grid">
            <label><span data-cms-key="jobs.modal.apply.position_label">Poste</span>
              <input type="text" name="position" id="applyPosition" required placeholder="Intitulé du poste">
            </label>
            <label class="file-field"><span data-cms-key="jobs.modal.apply.cv_label">CV</span>
              <input type="file" name="cv" accept=".pdf,.doc,.docx" required>
              <span data-file-label>Aucun fichier choisi</span>
            </label>
            <label class="file-field modal-form-full"><span data-cms-key="jobs.modal.apply.letter_label">Lettre de motivation</span>
              <input type="file" name="letter" accept=".pdf,.doc,.docx">
              <span data-file-label>Aucun fichier choisi</span>
            </label>
          </div>
        </div>

        <div class="apply-step" data-apply-step="2">
          <div class="apply-review-card">
            <h3 data-cms-key="jobs.modal.apply.review_title">Vérifiez votre candidature</h3>
            <p data-cms-key="jobs.modal.apply.review_desc">Contrôlez vos informations, puis envoyez votre candidature. Le CV est obligatoire pour permettre à nos équipes d'étudier votre profil.</p>
            <div class="apply-review-list" id="applyReviewList"></div>
          </div>
          <label class="modal-form-full"><span data-cms-key="jobs.modal.apply.message_label">Message</span>
            <textarea name="message" rows="5" placeholder="Présentez brièvement votre profil..."></textarea>
          </label>
          <label class="modal-consent">
            <input type="checkbox" required>
            <span data-cms-key="jobs.modal.apply.consent">Je confirme l'exactitude des informations transmises et j'accepte leur traitement dans le cadre de ma candidature.</span>
          </label>
          <div class="modal-cndp-card">
            <img src="{{ asset('images/cndp-logo.png') }}" alt="CNDP" class="modal-cndp-logo" data-cms-img="jobs.modal.apply.cndp_logo">
            <p class="modal-cndp-note" data-cms-key="jobs.modal.apply.cndp_note">
              Les informations saisies dans ce formulaire sont utilisées, collectées pour la finalité de traitement de votre candidature. Elles sont traitées par RHS GROUP et ne seront utilisées qu'à cette fin. Conformément à la loi 09-08, vous disposez d'un droit d'accès, de rectification et d'opposition en nous contactant à contact@rhsgroup.ma. Ce traitement a fait l'objet d'une autorisation auprès de la CNDP sous le numéro A-RH-2131/2025.
            </p>
          </div>
        </div>

        <div class="rhs-modal-actions">
          <button type="button" class="btn-outline-red" data-apply-prev data-cms-key="jobs.modal.apply.prev_btn" hidden>Précédent</button>
          <button type="button" class="btn-primary" data-apply-next><span data-cms-key="jobs.modal.apply.next_btn">Suivant</span> <span>→</span></button>
          <button type="submit" class="btn-primary" data-apply-submit data-cms-key="jobs.modal.apply.submit_btn" hidden>Envoyer la candidature</button>
        </div>

      </form>
    </section>
  </div>

  <div class="apply-toast" data-apply-toast hidden role="status" aria-live="polite">
    <strong>Candidature envoyée.</strong>
    <span>Merci, votre candidature a bien été transmise. Nos équipes reviendront vers vous après étude de votre profil.</span>
  </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/jobs.css') }}">
@endpush
@php
  $jobModalOfferModels = $offers->getCollection();

  if (!empty($selectedOffer) && !$jobModalOfferModels->contains('id', $selectedOffer->id)) {
    $jobModalOfferModels = $jobModalOfferModels->prepend($selectedOffer);
  }

  $jobModalOffers = $jobModalOfferModels->map(fn ($offer) => [
    'id' => $offer->id,
    'title' => $offer->title,
    'company' => $offer->company ?: 'RHS GROUP',
    'location' => $offer->location ?: '',
    'contract' => $offer->contract_type ?: '',
    'sector' => $offer->sector ?: '',
    'published' => optional($offer->published_at)->format('d/m/Y') ?: '',
    'description' => $offer->description ?: ($offer->excerpt ?: ''),
    'missions' => $offer->missions ?: '',
    'requirements' => $offer->requirements ?: '',
    'image' => $offer->hero_image ? route('public.file', ['path' => $offer->hero_image]) : '',
  ])->values();
@endphp
@push('scripts')
<script>
(() => {
  const offers = @json($jobModalOffers);

  const esc = (s) => String(s ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');

  const nl2br = (s) => esc(s).replace(/\n/g, '<br>');

  const closeJobSelect = (wrap) => {
    const trigger = wrap.querySelector('[data-jobs-select-trigger]');
    const menu = wrap.querySelector('[data-jobs-select-menu]');
    if (!trigger || !menu) return;
    menu.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    wrap.classList.remove('is-open');
  };

  document.querySelectorAll('[data-jobs-select]').forEach((wrap) => {
    const select = wrap.querySelector('select');
    const trigger = wrap.querySelector('[data-jobs-select-trigger]');
    const label = wrap.querySelector('[data-jobs-select-label]');
    const menu = wrap.querySelector('[data-jobs-select-menu]');
    if (!select || !trigger || !label || !menu) return;

    const syncLabel = () => {
      label.textContent = select.options[select.selectedIndex]?.textContent.trim() || '';
    };

    const renderMenu = () => {
      menu.innerHTML = [...select.options].map((option, index) => {
        const active = index === select.selectedIndex;
        return `
          <button type="button" class="jobs-select-option${active ? ' is-active' : ''}" role="option" aria-selected="${active}" data-option-index="${index}">
            ${esc(option.textContent.trim())}
          </button>
        `;
      }).join('');
    };

    const openSelect = () => {
      document.querySelectorAll('[data-jobs-select].is-open').forEach((other) => {
        if (other !== wrap) closeJobSelect(other);
      });
      renderMenu();
      menu.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      wrap.classList.add('is-open');
    };

    trigger.addEventListener('click', () => {
      wrap.classList.contains('is-open') ? closeJobSelect(wrap) : openSelect();
    });

    menu.addEventListener('click', (event) => {
      const option = event.target.closest('[data-option-index]');
      if (!option) return;
      select.selectedIndex = Number(option.dataset.optionIndex);
      select.dispatchEvent(new Event('change', { bubbles: true }));
      syncLabel();
      closeJobSelect(wrap);
    });

    select.addEventListener('change', syncLabel);
    syncLabel();
  });

  document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-jobs-select].is-open').forEach((wrap) => {
      if (!wrap.contains(event.target)) closeJobSelect(wrap);
    });
  });

  const input = document.getElementById('q');
  const box   = document.getElementById('jobsSuggest');

  let t = null;
  let aborter = null;

  const hide = () => { box.hidden = true; box.innerHTML = ''; };
  const show = () => { box.hidden = false; };

  const render = (items) => {
    if (!items || !items.length) return hide();

    box.innerHTML = items.map(it => `
      <a class="jobs-suggest-item" href="{{ route('jobs') }}?offer=${encodeURIComponent(it.id)}#jobs-list">
        <div class="jobs-suggest-title">${esc(it.title || '')}</div>
        ${it.meta ? `<div class="jobs-suggest-meta">${esc(it.meta)}</div>` : ``}
      </a>
    `).join('');

    show();
  };

  const fetchSuggest = async (q) => {
    if (aborter) aborter.abort();
    aborter = new AbortController();

const url = "{{ route('jobs.suggest') }}" + "?q=" + encodeURIComponent(q);
    const res = await fetch(url, {
      headers: { 'Accept': 'application/json' },
      signal: aborter.signal
    });

    if (!res.ok) return hide();
    render(await res.json());
  };

  if (input && box) {
    input.addEventListener('input', () => {
      const q = input.value.trim();
      if (q.length < 2) return hide();

      clearTimeout(t);
      t = setTimeout(() => fetchSuggest(q).catch(hide), 160);
    });

    input.addEventListener('focus', () => {
      const q = input.value.trim();
      if (q.length >= 2) fetchSuggest(q).catch(hide);
    });

    document.addEventListener('click', (e) => {
      if (!box.contains(e.target) && e.target !== input) hide();
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') hide();
    });
  }

  const offerModal = document.getElementById('jobOfferModal');
  const applyModal = document.getElementById('jobApplyModal');
  const titleEl = document.getElementById('jobModalTitle');
  const metaEl = document.getElementById('jobModalMeta');
  const descEl = document.getElementById('jobModalDescription');
  const missionsEl = document.getElementById('jobModalMissions');
  const missionsWrap = document.getElementById('jobModalMissionsWrap');
  const reqEl = document.getElementById('jobModalRequirements');
  const reqWrap = document.getElementById('jobModalRequirementsWrap');
  const applyJobOfferId = document.getElementById('applyJobOfferId');
  const applyType = document.getElementById('applyType');
  const applyPosition = document.getElementById('applyPosition');
  const applyTitle = document.getElementById('applyModalTitle');
  const applySubtitle = document.getElementById('applyModalSubtitle');
  const applyForm = document.querySelector('.rhs-apply-steps');
  const applyReviewList = document.getElementById('applyReviewList');
  const applyToast = document.querySelector('[data-apply-toast]');
  let toastTimer = null;
  let activeOffer = null;
  let activeStep = 0;

  const openModal = (modal) => {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('rhs-modal-open');
  };

  const closeModals = () => {
    document.querySelectorAll('.rhs-modal').forEach((modal) => modal.setAttribute('aria-hidden', 'true'));
    document.documentElement.classList.remove('rhs-modal-open');
  };

  const showApplyToast = (type, title, text) => {
    if (!applyToast) return;
    applyToast.classList.toggle('is-error', type === 'error');
    applyToast.classList.toggle('is-success', type !== 'error');
    applyToast.querySelector('strong').textContent = title;
    applyToast.querySelector('span').textContent = text;
    applyToast.hidden = false;
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
      applyToast.hidden = true;
    }, 7000);
  };

  const setOptionalBlock = (wrap, target, value) => {
    const hasValue = String(value || '').trim().length > 0;
    wrap.hidden = !hasValue;
    target.innerHTML = hasValue ? nl2br(value) : '';
  };

  const openOffer = (id) => {
    activeOffer = offers.find((offer) => String(offer.id) === String(id));
    if (!activeOffer || !offerModal) return;

    titleEl.textContent = activeOffer.title;
    metaEl.innerHTML = [activeOffer.company, activeOffer.location, activeOffer.contract, activeOffer.sector, activeOffer.published]
      .filter(Boolean)
      .map((item) => `<span>${esc(item)}</span>`)
      .join('');
    descEl.innerHTML = nl2br(activeOffer.description || 'Notre équipe vous communiquera les détails de cette opportunité lors du premier échange.');
    setOptionalBlock(missionsWrap, missionsEl, activeOffer.missions);
    setOptionalBlock(reqWrap, reqEl, activeOffer.requirements);
    openModal(offerModal);
  };

  const openRequestedOffer = () => {
    const params = new URLSearchParams(window.location.search);
    const requestedOfferId = params.get('offer');

    if (!requestedOfferId) return;

    window.setTimeout(() => {
      openOffer(requestedOfferId);
    }, 120);
  };

  const updateApplyStep = (nextStep) => {
    activeStep = Math.max(0, Math.min(2, nextStep));
    document.querySelectorAll('[data-apply-step]').forEach((step, index) => {
      step.classList.toggle('is-active', index === activeStep);
    });
    document.querySelectorAll('[data-step-indicator]').forEach((indicator, index) => {
      indicator.classList.toggle('is-active', index === activeStep);
      indicator.classList.toggle('is-complete', index < activeStep);
    });
    document.querySelector('[data-apply-prev]').hidden = activeStep === 0;
    document.querySelector('[data-apply-next]').hidden = activeStep === 2;
    document.querySelector('[data-apply-submit]').hidden = activeStep !== 2;
    if (activeStep === 2) updateReview();
  };

  const updateReview = () => {
    if (!applyForm || !applyReviewList) return;
    const data = new FormData(applyForm);
    const cvName = applyForm.querySelector('input[name="cv"]')?.files?.[0]?.name || 'CV non ajouté';
    const rows = [
      ['Nom complet', data.get('full_name') || ''],
      ['Email', data.get('email') || ''],
      ['Téléphone', data.get('phone') || ''],
      ['Ville', data.get('city') || ''],
      ['Poste', data.get('position') || ''],
      ['CV', cvName],
    ];
    applyReviewList.innerHTML = rows
      .map(([label, value]) => `<div><span>${esc(label)}</span><strong>${esc(value || 'Non renseigné')}</strong></div>`)
      .join('');
  };

  const currentStepIsValid = () => {
    const step = document.querySelector(`[data-apply-step="${activeStep}"]`);
    if (!step) return true;
    const fields = [...step.querySelectorAll('input, select, textarea')];
    return fields.every((field) => field.checkValidity() || (field.reportValidity(), false));
  };

  const openApply = (offer = activeOffer, spontaneous = false) => {
    if (!applyModal) return;
    closeModals();
    applyForm?.reset();
    applyJobOfferId.value = spontaneous || !offer ? '' : offer.id;
    applyType.value = spontaneous ? 'spontaneous' : 'job';
    applyPosition.value = spontaneous ? 'Candidature spontanée' : (offer?.title || '');
    applyTitle.textContent = spontaneous ? 'Candidature spontanée' : 'Postuler à cette offre';
    applySubtitle.textContent = spontaneous
      ? 'Présentez votre profil et nos équipes vous orienteront vers les opportunités adaptées.'
      : `Vous postulez pour : ${offer?.title || 'cette offre'}.`;
    updateApplyStep(0);
    document.querySelectorAll('[data-file-label]').forEach((label) => { label.textContent = 'Aucun fichier choisi'; });
    if (applyToast) applyToast.hidden = true;
    openModal(applyModal);
  };

  document.querySelectorAll('[data-rhs-open-offer]').forEach((btn) => {
    btn.addEventListener('click', () => openOffer(btn.dataset.offerId));
  });

  document.querySelectorAll('[data-rhs-close-modal]').forEach((btn) => {
    btn.addEventListener('click', closeModals);
  });

  document.querySelector('[data-rhs-open-apply]')?.addEventListener('click', () => openApply());

  document.querySelector('a[href*="type=spontaneous"]')?.addEventListener('click', (event) => {
    event.preventDefault();
    openApply(null, true);
  });

  document.querySelector('[data-apply-next]')?.addEventListener('click', () => {
    if (currentStepIsValid()) updateApplyStep(activeStep + 1);
  });

  document.querySelector('[data-apply-prev]')?.addEventListener('click', () => updateApplyStep(activeStep - 1));

  document.querySelectorAll('[data-step-indicator]').forEach((indicator) => {
    indicator.addEventListener('click', () => {
      const target = Number(indicator.dataset.stepIndicator || 0);
      if (target <= activeStep) updateApplyStep(target);
    });
  });

  document.querySelectorAll('.file-field input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      const label = input.closest('.file-field')?.querySelector('[data-file-label]');
      if (label) label.textContent = input.files?.[0]?.name || 'Aucun fichier choisi';
    });
  });

  applyForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!applyForm.checkValidity()) {
      applyForm.reportValidity();
      return;
    }
    const submit = document.querySelector('[data-apply-submit]');
    if (!submit || submit.disabled) return;
    submit.disabled = true;
    const submitLabel = submit.textContent;
    submit.textContent = 'Envoi en cours...';

    try {
      const res = await fetch(applyForm.action, {
        method: 'POST',
        body: new FormData(applyForm),
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });

      if (!res.ok && res.status !== 302) {
        let errorMessage = 'Merci de vérifier les champs obligatoires, notamment votre CV, puis de réessayer.';
        try {
          const payload = await res.json();
          const errors = payload.errors ? Object.values(payload.errors).flat() : [];
          errorMessage = errors[0] || payload.message || errorMessage;
        } catch (jsonError) {}
        throw new Error(errorMessage);
      }
      applyForm.reset();
      document.querySelectorAll('[data-file-label]').forEach((label) => { label.textContent = 'Aucun fichier choisi'; });
      closeModals();
      showApplyToast(
        'success',
        'Candidature envoyée.',
        'Merci, votre candidature a bien été transmise. Nos équipes reviendront vers vous après étude de votre profil.'
      );
    } catch (error) {
      showApplyToast('error', 'Candidature non envoyée.', error.message || 'Merci de vérifier les champs obligatoires, puis de réessayer.');
    } finally {
      submit.disabled = false;
      submit.textContent = submitLabel || 'Envoyer la candidature';
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeModals();
  });

  openRequestedOffer();
})();
</script>
@endpush

