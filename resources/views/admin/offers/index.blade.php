@extends('admin.layouts.app')
@section('title','Admin – Offres')
@section('page_title','Offres')

@section('page_subtitle')
Gérez vos offres : création, mise à jour, activation et suppression
@endsection

@section('top_actions')
  <a class="btn btn-primary" href="{{ route('admin.offers.create') }}">
    <span class="btn-ico" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </span>
    Nouvelle offre
  </a>
@endsection

@section('content')

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">
        Liste des offres
        <span class="panel-badge">{{ method_exists($offers,'total') ? $offers->total() : count($offers) }}</span>
      </div>

      <div class="panel-tools">
        <form method="GET" class="table-controls" action="{{ url()->current() }}" autocomplete="off">

          {{-- SEARCH --}}
          <div class="table-search">
            <span class="table-search-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>

            <input
              id="offersSearch"
              type="text"
              name="q"
              value="{{ request('q') }}"
              placeholder="Rechercher par titre..."
            >
            <div id="offersSuggest" class="search-suggest" hidden></div>
          </div>

          {{-- STATUS --}}
          <div class="table-filter">
            <label for="status" class="sr-only">Statut</label>
            <select name="status" id="status">
              <option value="all" {{ request('status','all') === 'all' ? 'selected' : '' }}>Tous</option>
              <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs</option>
              <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
            </select>
          </div>

          {{-- CONTRACT --}}
          <div class="table-filter">
            <label for="contract" class="sr-only">Contrat</label>
            <select name="contract" id="contract">
              <option value="" {{ request('contract','') === '' ? 'selected' : '' }}>Tous contrats</option>
              @foreach(($contracts ?? collect()) as $c)
                <option value="{{ $c }}" {{ request('contract') === $c ? 'selected' : '' }}>{{ $c }}</option>
              @endforeach
            </select>
          </div>

          <div class="table-ctrl-actions">
            <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
            <a class="btn btn-ghost btn-sm" href="{{ url()->current() }}">Réinitialiser</a>
          </div>

        </form>
      </div>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Offre</th>
            <th>Lieu</th>
            <th>Contrat</th>
            <th>Statut</th>
            <th class="th-actions">Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse($offers as $offer)
            <tr>
              <td>
                <div class="cell-main">
                  <div class="cell-title">{{ $offer->title }}</div>
                  <div class="cell-sub">/offres/{{ $offer->slug }}</div>
                </div>
              </td>

              <td><span class="pill pill-neutral">{{ $offer->location ?: '—' }}</span></td>
              <td><span class="pill pill-neutral">{{ $offer->contract_type ?: '—' }}</span></td>

              <td>
                @if($offer->is_active)
                  <span class="pill pill-success">Actif</span>
                @else
                  <span class="pill pill-danger">Inactif</span>
                @endif
              </td>

              <td class="td-actions">
      {{-- ✅ Publish (sets published_at) --}}
@if(!$offer->is_active)
  <form
    action="{{ route('admin.offers.publish', $offer) }}"
    method="POST"
    style="display:inline;"
  >
    @csrf
    @method('PATCH') <!-- required because route uses PATCH -->

    <button class="btn btn-success btn-sm" type="submit">
      Publier
    </button>
  </form>
@endif

  {{-- ✏️ Edit --}}
  <a class="icon-btn" href="{{ route('admin.offers.edit', $offer) }}" title="Modifier">
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </a>

  {{-- 🔗 QR to public offer page --}}
@php
  $offerUrl = route('jobs.show', $offer->slug);

  // QR image (inline preview)
  $qrImg = route('qr', ['url' => rawurlencode($offerUrl)]) . '?filename=' . rawurlencode('qr-offre-' . $offer->id);

  // QR download
  $qrDownload = route('qr', ['url' => rawurlencode($offerUrl)]) . '?download=1&filename=' . rawurlencode('qr-offre-' . $offer->id);
@endphp

<button
  type="button"
  class="icon-btn js-share-offer"
  title="Partager l’offre"
  data-title="{{ e($offer->title) }}"
  data-url="{{ $offerUrl }}"
  data-qr="{{ $qrImg }}"
  data-qrdl="{{ $qrDownload }}"
>
  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M15 8a3 3 0 1 0-2.83-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M6 14a3 3 0 1 0 2.83 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M8.7 15.7 15.3 19.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M15.3 4.7 8.7 8.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    <path d="M18 9a3 3 0 1 0 0 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </svg>
</button>




  {{-- 🗑 Delete --}}
  <form action="{{ route('admin.offers.destroy', $offer) }}" method="POST" onsubmit="return confirm('Supprimer cette offre ?')">
    @csrf
    @method('DELETE')
    <button class="icon-btn icon-btn-danger" type="submit" title="Supprimer">
      <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M8 6V4h8v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>
  </form>


</td>

            </tr>
          @empty
            <tr>
              <td colspan="5">
                <div class="table-empty">
                  <div class="table-empty-ico">📄</div>
                  <div class="table-empty-title">Aucune offre trouvée</div>
                  <div class="table-empty-sub">Ajustez la recherche ou les filtres.</div>
                  <a class="btn btn-primary" href="{{ route('admin.offers.create') }}">Créer une offre</a>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
      {{-- ================= SHARE MODAL ================= --}}
<div id="shareModal" class="share-modal" hidden aria-hidden="true">
  <div class="share-backdrop" data-share-close></div>

  <div class="share-dialog" role="dialog" aria-modal="true" aria-labelledby="shareTitle">
    <button class="share-close" type="button" title="Fermer" data-share-close>×</button>

    <h3 id="shareTitle" class="share-title">Partager l’offre</h3>

    <div class="share-qr-wrap">
      <img id="shareQrImg" src="" alt="QR Code" class="share-qr">

      <a id="shareQrDownload" class="share-qr-dl" href="#" title="Télécharger le QR" download>
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 3v10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M8 9l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M4 17v3h16v-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </a>
    </div>

    <div class="share-link-wrap">
      <input id="shareLinkInput" type="text" readonly value="">
      <button id="shareCopyBtn" class="share-copy" type="button" title="Copier le lien">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M8 8h10v12H8V8Z" stroke="currentColor" stroke-width="2" />
          <path d="M6 16H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <div class="share-foot">
      <span class="share-hint">Scannez le QR ou copiez le lien</span>
    </div>
  </div>
</div>

<div id="copyToast" class="copy-toast" hidden>
  Lien copié dans le presse-papiers
</div>

    </div>

    @if(method_exists($offers,'links'))
      <div class="panel-foot">
        {{ $offers->links() }}
      </div>
    @endif
  </div>
<div id="copyToast" class="copy-toast" hidden>
  Lien copié dans le presse-papiers
</div>

@endsection

@push('scripts')
<script>
(() => {
  const input = document.getElementById('offersSearch');
  const box = document.getElementById('offersSuggest');
  if (!input || !box) return;

  let t = null;
  let aborter = null;

  const hide = () => { box.hidden = true; box.innerHTML = ''; };
  const show = () => { box.hidden = false; };

  const esc = (s) => String(s)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');

  const render = (items) => {
    if (!items || !items.length) return hide();
    box.innerHTML = items.map(it => `
      <a class="suggest-item" href="/admin/offers/${it.id}/edit">
        <div class="suggest-title">${esc(it.title || '')}</div>
        <div class="suggest-meta">${esc(it.meta || '')}</div>
      </a>
    `).join('');
    show();
  };

  const fetchSuggest = async (q) => {
    if (aborter) aborter.abort();
    aborter = new AbortController();
    const url = "{{ route('admin.offers.suggest') }}" + "?q=" + encodeURIComponent(q);

    const res = await fetch(url, { headers: { 'Accept': 'application/json' }, signal: aborter.signal });
    if (!res.ok) return hide();
    render(await res.json());
  };

  input.addEventListener('input', () => {
    const q = input.value.trim();
    if (q.length < 2) return hide();
    clearTimeout(t);
    t = setTimeout(() => fetchSuggest(q).catch(hide), 180);
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
})();
</script>
@endpush
@push('scripts')
<script>
(() => {
  const modal = document.getElementById('shareModal');
  const qrImg = document.getElementById('shareQrImg');
  const qrDl  = document.getElementById('shareQrDownload');
  const linkI = document.getElementById('shareLinkInput');
  const copyB = document.getElementById('shareCopyBtn');

  const toast = document.getElementById('copyToast');

 const openModal = () => {
  modal.hidden = false;
  modal.setAttribute('aria-hidden', 'false');
  modal.classList.add('is-open');
  document.body.style.overflow = 'hidden';
};

const closeModal = () => {
  modal.classList.remove('is-open');

  setTimeout(() => {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }, 260); // match CSS animation
};


  const showToast = (msg = 'Lien copié dans le presse-papiers') => {
    if (!toast) return;
    toast.textContent = msg;
    toast.hidden = false;
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.hidden = true, 220);
    }, 1600);
  };

  // Open share modal from table button
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-share-offer');
    if (!btn) return;

    const url  = btn.dataset.url || '';
    const qr   = btn.dataset.qr || '';
    const qrdl = btn.dataset.qrdl || '';

    // Fill modal
    if (qrImg) qrImg.src = qr;
    if (qrDl)  qrDl.href = qrdl;
    if (linkI) linkI.value = url;

    openModal();
  });

  // Close handlers
  document.addEventListener('click', (e) => {
    if (!modal || modal.hidden) return;
    if (e.target.closest('[data-share-close]')) closeModal();
  });

  document.addEventListener('keydown', (e) => {
    if (!modal || modal.hidden) return;
    if (e.key === 'Escape') closeModal();
  });

  // Copy link
  if (copyB) {
    copyB.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(linkI.value || '');
        copyB.innerHTML = '✅';
        setTimeout(() => {
          copyB.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M8 8h10v12H8V8Z" stroke="currentColor" stroke-width="2" />
              <path d="M6 16H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          `;
        }, 700);

        showToast();
      } catch {
        alert('Impossible de copier le lien');
      }
    });
  }
})();
</script>
@endpush


