@extends('admin.layouts.app')
@section('title','Catalogue formations')
@section('page_title','Catalogue formations')

@section('page_subtitle')
Gérez vos formations : création, mise à jour, activation et suppression
@endsection

@section('top_actions')
<a class="btn btn-primary" href="{{ route('admin.formations.create') }}">
  <span class="btn-ico" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none">
      <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
  </span>
  Nouvelle formation
</a>
@endsection

@section('content')
<div class="panel">
  <div class="panel-head">
    <div class="panel-title">
      Liste des formations
      <span class="panel-badge">{{ method_exists($formations,'total') ? $formations->total() : count($formations) }}</span>
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
          <input type="text" name="q" id="formationsSearch" placeholder="Rechercher par titre..." value="{{ request('q') }}">
          <div id="formationsSuggest" class="search-suggest" hidden></div>
        </div>

        {{-- DOMAIN FILTER --}}
        <div class="table-filter">
          <label for="domain" class="sr-only">Domaine</label>
          <select name="domain" id="domain">
            <option value="">Tous domaines</option>
            @foreach(($domains ?? []) as $d)
              <option value="{{ $d }}" {{ request('domain') === $d ? 'selected' : '' }}>{{ ucfirst($d) }}</option>
            @endforeach
          </select>
        </div>

        {{-- STATUS --}}
        <div class="table-filter">
          <label for="status" class="sr-only">Statut</label>
          <select name="status" id="status">
            <option value="all" {{ request('status','all')==='all' ? 'selected':'' }}>Tous</option>
            <option value="featured" {{ request('status')==='featured' ? 'selected':'' }}>Mise en avant</option>
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
          <th>Titre</th>
          <th>Domaine</th>
          <th>Durée</th>
          <th>Mise en avant</th>
          <th class="th-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($formations as $f)
        <tr>
          <td>{{ $f->title }}</td>
          <td>{{ ucfirst($f->domain) }}</td>
          <td>{{ $f->duration ?: '—' }}</td>
          <td>
            @if($f->featured)
              <span class="pill pill-success">Oui</span>
            @else
              <span class="pill pill-neutral">Non</span>
            @endif
          </td>
          <td class="td-actions">
            <a class="icon-btn" href="{{ route('admin.formations.edit',$f) }}" title="Modifier">
              <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>

            <form action="{{ route('admin.formations.destroy',$f) }}" method="POST" onsubmit="return confirm('Supprimer cette formation ?')" style="display:inline">
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
              <div class="table-empty-title">Aucune formation trouvée</div>
              <div class="table-empty-sub">Ajustez la recherche ou les filtres.</div>
              <a class="btn btn-primary" href="{{ route('admin.formations.create') }}">Créer une formation</a>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if(method_exists($formations,'links'))
    <div class="panel-foot">
      {{ $formations->links() }}
    </div>
  @endif
</div>

@endsection

@push('scripts')
<script>
(() => {
  const input = document.getElementById('formationsSearch');
  const box = document.getElementById('formationsSuggest');
  if (!input || !box) return;

  let t = null;
  let aborter = null;
  const hide = () => { box.hidden=true; box.innerHTML=''; };
  const show = () => { box.hidden=false; };

  const esc = (s) => String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');

  const render = items => {
    if(!items||!items.length) return hide();
    box.innerHTML = items.map(it => `
      <a class="suggest-item" href="/admin/formations/${it.id}/edit">
        <div class="suggest-title">${esc(it.title||'')}</div>
        <div class="suggest-meta">${esc(it.domain||'')}</div>
      </a>
    `).join('');
    show();
  };

  const fetchSuggest = async q => {
    if(aborter) aborter.abort();
    aborter = new AbortController();
    const res = await fetch("{{ route('admin.formations.suggest') }}?q="+encodeURIComponent(q), {headers:{'Accept':'application/json'}, signal:aborter.signal});
    if(!res.ok) return hide();
    render(await res.json());
  };

  input.addEventListener('input',()=>{ const q=input.value.trim(); if(q.length<2) return hide(); clearTimeout(t); t=setTimeout(()=>fetchSuggest(q).catch(hide),180); });
  input.addEventListener('focus',()=>{ const q=input.value.trim(); if(q.length>=2) fetchSuggest(q).catch(hide); });
  document.addEventListener('click', e => { if(!box.contains(e.target) && e.target!==input) hide(); });
  input.addEventListener('keydown', e => { if(e.key==='Escape') hide(); });
})();
</script>
@endpush
