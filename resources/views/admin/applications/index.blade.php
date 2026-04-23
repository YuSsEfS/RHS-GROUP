@extends('admin.layouts.app')
@section('title','Admin – Candidatures')
@section('page_title','Candidatures')

@section('page_subtitle')
Consultez, filtrez et recherchez les candidatures reçues
@endsection

@section('content')

  <div class="panel">
    <div class="panel-head">
      <div class="panel-title">
        Liste des candidatures
        <span class="panel-badge">{{ method_exists($applications,'total') ? $applications->total() : count($applications) }}</span>
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
              id="appsSearch"
              type="text"
              name="q"
              value="{{ request('q') }}"
placeholder="Rechercher (nom, email ou ville)..."
              autocomplete="off"
              spellcheck="false"
            >
            <div id="appsSuggest" class="search-suggest" hidden></div>
          </div>

          {{-- FILTER: READ STATUS --}}
          <div class="table-filter">
            <label for="status" class="sr-only">Statut</label>
            <select name="status" id="status">
              <option value="all" {{ request('status','all') === 'all' ? 'selected' : '' }}>Tous</option>
              <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Non lus</option>
              <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Lus</option>
            </select>
          </div>

          {{-- FILTER: OFFER --}}
          <div class="table-filter">
            <label for="offer" class="sr-only">Offre</label>
            <select name="offer" id="offer">
              <option value="all" {{ request('offer','all') === 'all' ? 'selected' : '' }}>Toutes offres</option>
              <option value="spontaneous" {{ request('offer') === 'spontaneous' ? 'selected' : '' }}>Spontanées</option>
              @foreach(($offers ?? collect()) as $o)
                <option value="{{ $o->id }}" {{ (string)request('offer') === (string)$o->id ? 'selected' : '' }}>
                  {{ $o->title }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- ACTIONS --}}
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
            <th>Candidat</th>
            <th>Email</th>
            <th>Offre</th>
            <th>Statut</th>
            <th class="th-actions">Action</th>
          </tr>
        </thead>

        <tbody>
          @forelse($applications as $a)
            <tr>
              <td>
                <div class="cell-main">
                  <div class="cell-title">{{ $a->full_name }}</div>
                  <div class="cell-sub">
                    {{ $a->phone ?: '—' }}
                    @if(!empty($a->city)) • {{ $a->city }} @endif
                  </div>
                </div>
              </td>

              <td><span class="pill pill-neutral">{{ $a->email }}</span></td>

              <td>
                <span class="pill pill-neutral">
                  {{ $a->offer?->title ?? 'Spontanée' }}
                </span>
              </td>

              <td>
                @if($a->is_read)
                  <span class="pill pill-success">Lu</span>
                @else
                  <span class="pill pill-danger">Non lu</span>
                @endif
              </td>

              <td class="td-actions">
                <a class="btn btn-ghost btn-sm" href="{{ route('admin.applications.show', $a->id) }}">
                  Ouvrir
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5">
                <div class="table-empty">
                  <div class="table-empty-ico">📩</div>
                  <div class="table-empty-title">Aucune candidature</div>
                  <div class="table-empty-sub">Ajustez la recherche ou les filtres.</div>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($applications,'links'))
      <div class="panel-foot">
        {{ $applications->links() }}
      </div>
    @endif
  </div>

@endsection

@push('scripts')
<script>
(() => {
  const input = document.getElementById('appsSearch');
  const box = document.getElementById('appsSuggest');
  if (!input || !box) return;

  const endpoint = @json(route('admin.applications.suggest'));
  let t = null;
  let aborter = null;

  const hide = () => { box.hidden = true; box.innerHTML = ''; };
  const show = () => { box.hidden = false; };

  const esc = (s) => String(s ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');

  const render = (items) => {
    if (!Array.isArray(items) || items.length === 0) return hide();

    box.innerHTML = items.map(it => `
      <a class="suggest-item" href="/admin/applications/${it.id}">
        <div class="suggest-title">${esc(it.title)}</div>
        <div class="suggest-meta">${esc(it.meta)}</div>
      </a>
    `).join('');

    show();
  };

  const fetchSuggest = async (q) => {
    if (aborter) aborter.abort();
    aborter = new AbortController();

    const url = endpoint + '?q=' + encodeURIComponent(q);

    const res = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      signal: aborter.signal
    });

    if (!res.ok) return hide();
    const data = await res.json();
    render(data);
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
