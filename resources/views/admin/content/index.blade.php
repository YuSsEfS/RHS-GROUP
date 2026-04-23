@extends('admin.layouts.app')
@section('title','Admin – Contenu')
@section('page_title','Contenu du site')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-content.css') }}">
@endpush

@section('page_subtitle')
Gérez les textes & images par page. Les médias sont stockés dans <code>storage/app/public/media</code>.
@endsection

@section('top_actions')
  <a href="{{ route('admin.content.builder') }}" class="btn btn-primary">
    <span class="btn-ico" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M4 7h16M4 12h10M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </span>
    Ouvrir Builder
  </a>

  <!-- <button type="button" class="btn btn-ghost" id="acExpandAll">Tout ouvrir</button>
  <button type="button" class="btn btn-ghost" id="acCollapseAll">Tout fermer</button> -->
@endsection

@section('content')
<div class="ac-wrap">

  @if(session('success'))
    <div class="ac-alert-success">
      {{ session('success') }}
    </div>
  @endif

  @php
    $pagesGrouped = $blocks->groupBy('page');
    $pagesCount = $pagesGrouped->count();
    $fieldsCount = $blocks->count();
    $imagesCount = $blocks->filter(fn($b) => str_contains($b->field, 'image'))->count();
    $textsCount = $fieldsCount - $imagesCount;
  @endphp

  {{-- SUMMARY --}}
  <div class="ac-summary">
    <div class="ac-stat">
      <div class="ac-stat-label">Pages</div>
      <div class="ac-stat-value">{{ $pagesCount }}</div>
    </div>

    <div class="ac-stat">
      <div class="ac-stat-label">Champs</div>
      <div class="ac-stat-value">{{ $fieldsCount }}</div>
    </div>

    <div class="ac-stat">
      <div class="ac-stat-label">Textes</div>
      <div class="ac-stat-value">{{ $textsCount }}</div>
    </div>

    <div class="ac-stat">
      <div class="ac-stat-label">Images</div>
      <div class="ac-stat-value">{{ $imagesCount }}</div>
    </div>

    <div class="ac-hint">
      <strong>Astuce :</strong> ouvrez une page, modifiez, puis cliquez <em>Enregistrer</em>.
      Les retours à la ligne sont conservés.
    </div>
  </div>

  <form method="POST" action="{{ route('admin.content.save') }}" enctype="multipart/form-data">
    @csrf

    {{-- PAGES --}}
    <div class="ac-pages">
      @foreach($pagesGrouped as $page => $pageBlocks)
        @php
          $pageImages = $pageBlocks->filter(fn($b) => str_contains($b->field,'image'))->count();
          $pageTexts  = $pageBlocks->count() - $pageImages;
        @endphp

        <section class="ac-page" data-page="{{ strtolower($page) }}">
          <button type="button" class="ac-page-head" data-toggle>
            <div class="ac-page-title">
              <span class="ac-page-dot"></span>
              <span>Page: <strong>{{ $page }}</strong></span>
              <span class="ac-count">{{ $pageBlocks->count() }} champs</span>
              <span class="ac-mini">• {{ $pageTexts }} textes</span>
              <span class="ac-mini">• {{ $pageImages }} images</span>
            </div>

            <span class="ac-chevron">▾</span>
          </button>

          <div class="ac-page-body">

            {{-- FIELDS --}}
            @foreach($pageBlocks as $b)
              @php
                $label = $b->section . ' / ' . $b->field;
                $isImage = str_contains($b->field, 'image');
              @endphp

              <div class="ac-field">
                <div class="ac-field-left">
                  <div class="ac-field-label">{{ $label }}</div>

                  <div class="ac-field-meta">
                    <span class="ac-key">ID: {{ $b->id }}</span>
                    <span class="ac-pill {{ $isImage ? 'is-image' : 'is-text' }}">
                      {{ $isImage ? 'Image' : 'Texte' }}
                    </span>
                  </div>
                </div>

                <div class="ac-field-right">
                  @if($isImage)
                    <div class="ac-image-row">
                      <input class="ac-file" type="file" name="images[{{ $b->id }}]">

                      @if($b->value)
                        <a class="ac-link" href="{{ asset('storage/'.$b->value) }}" target="_blank" rel="noopener">
                          Voir image
                        </a>

                        <div class="ac-thumb">
                          <img src="{{ asset('storage/'.$b->value) }}" alt="preview">
                        </div>
                      @else
                        <span class="ac-muted">Aucune image</span>
                      @endif
                    </div>
                  @else
                    <textarea class="ac-textarea"
                              name="blocks[{{ $b->id }}]"
                              rows="2"
                              placeholder="Écrire ici...">{{ $b->value }}</textarea>

                    <div class="ac-help">
                      <span>Texte long autorisé • retours à la ligne conservés</span>
                    </div>
                  @endif
                </div>
              </div>
            @endforeach

          </div>
        </section>
      @endforeach
    </div>

    <div class="ac-actions">
      <button class="btn btn-primary" type="submit">
        Enregistrer
      </button>
    </div>

  </form>
</div>

@push('scripts')
<script>
(() => {
  const expandAllBtn = document.getElementById('acExpandAll');
  const collapseAllBtn = document.getElementById('acCollapseAll');

  // Toggle one page
  document.querySelectorAll('[data-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      const section = btn.closest('.ac-page');
      section.classList.toggle('open');
    });
  });

  // Expand/Collapse all
  function setAll(open) {
    document.querySelectorAll('.ac-page').forEach(p => {
      p.classList.toggle('open', open);
    });
  }
  expandAllBtn?.addEventListener('click', () => setAll(true));
  collapseAllBtn?.addEventListener('click', () => setAll(false));

  // Default open first page
  const first = document.querySelector('.ac-page');
  if (first) first.classList.add('open');

  // Auto-grow textareas
  document.querySelectorAll('.ac-textarea').forEach(t => {
    const grow = () => {
      t.style.height = 'auto';
      t.style.height = (t.scrollHeight + 2) + 'px';
    };
    grow();
    t.addEventListener('input', grow);
  });
})();
</script>
@endpush
@endsection
