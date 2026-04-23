@extends('admin.layouts.app')
@section('title','Admin – Builder')
@section('page_title','Builder (édition visuelle)')

@push('styles')
<style>
  /* ============================================================
     MAKE ADMIN CONTENT FULL WIDTH (override admin container)
  ============================================================ */
  .admin-page-content,
  .admin-content,
  .admin-main,
  .admin-container,
  .container,
  .max-w-7xl {
    max-width: 100% !important;
    width: 100% !important;
  }

  /* ============================================================
     BUILDER LAYOUT
  ============================================================ */
  .builder-shell{
    width: 100%;
    display: grid;
    grid-template-columns: 420px 10px 1fr;
    gap: 14px;
    align-items: start;
  }

  /* Resizer */
  .builder-resizer{
    height: calc(100vh - 140px);
    position: sticky;
    top: 86px;
    border-radius: 10px;
    background: rgba(15,23,42,.06);
    border: 1px solid rgba(15,23,42,.12);
    cursor: col-resize;
  }

  /* Left panel */
  .builder-panel{
    position: sticky;
    top: 86px;
    height: calc(100vh - 140px);
    overflow: auto;

    background: #fff;
    border: 1px solid rgba(15,23,42,.12);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,.06);

    padding: 14px 14px 18px;
  }

  .b-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
    margin-bottom: 10px;
  }
  .b-title{
    font-weight: 950;
    margin: 0;
    font-size: 16px;
    letter-spacing: -.2px;
  }
  .b-sub{
    margin: 6px 0 0;
    color: #64748b;
    font-weight: 700;
    font-size: 12.5px;
    line-height: 1.5;
  }

  .b-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding: 6px 10px;
    border-radius: 999px;
    border: 1px solid rgba(15,23,42,.12);
    background: #fafafa;
    font-size: 12px;
    font-weight: 900;
    color: #334155;
    white-space: nowrap;
  }
  .dot{
    width:8px; height:8px; border-radius:999px;
    background:#e23b31;
    display:inline-block;
  }

  .b-divider{
    height:1px;
    background: rgba(15,23,42,.08);
    margin: 12px 0;
  }

  .b-row{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  .b-btn{
    height: 42px;
    padding: 0 14px;
    border-radius: 14px;
    border: 1px solid rgba(15,23,42,.14);
    background: #fff;
    font-weight: 950;
    cursor: pointer;
    transition: .18s ease;
  }
  .b-btn:hover{ transform: translateY(-1px); box-shadow: 0 10px 20px rgba(0,0,0,.06); }

  .b-btn-primary{
    background: #e23b31;
    color: #fff;
    border-color: #e23b31;
  }
  .b-btn-primary:hover{
    background: #c92927;
    border-color: #c92927;
  }
  .b-btn-ghost{
    background: #f8fafc;
  }

  .b-field{
    margin-top: 12px;
  }
  .b-label{
    display:block;
    font-size: 12px;
    font-weight: 950;
    color: #0f172a;
    margin-bottom: 6px;
  }

  .b-select,
  .b-input{
    width: 100%;
    height: 42px;
    border-radius: 14px;
    border: 1px solid rgba(15,23,42,.14);
    padding: 0 12px;
    font-weight: 800;
    outline: none;
    background: #fff;
  }

  .b-select:focus,
  .b-input:focus{
    border-color: rgba(226,59,49,.55);
    box-shadow: 0 0 0 4px rgba(226,59,49,.10);
  }

  .b-help{
    margin-top: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    line-height: 1.45;
  }

  .b-selected{
    margin-top: 12px;
    padding: 12px;
    border-radius: 16px;
    border: 1px dashed rgba(15,23,42,.22);
    background: #fbfbfb;
  }

  .b-key{
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 12px;
    color: #0f172a;
    font-weight: 950;
    padding: 8px 10px;
    border-radius: 12px;
    background: rgba(15,23,42,.06);
    border: 1px solid rgba(15,23,42,.10);
    overflow:auto;
  }

  .b-textarea{
    width: 100%;
    min-height: 140px;
    border-radius: 14px;
    border: 1px solid rgba(15,23,42,.14);
    padding: 10px 12px;
    font-weight: 800;
    outline: none;
  }
  .b-textarea:focus{
    border-color: rgba(226,59,49,.55);
    box-shadow: 0 0 0 4px rgba(226,59,49,.10);
  }

  .b-status{
    margin-top: 12px;
    font-size: 12.5px;
    font-weight: 900;
    color: #334155;
    line-height: 1.5;
  }
  .b-status small{
    display:block;
    margin-top: 2px;
    font-weight: 800;
    color:#64748b;
  }

  /* Preview */
  .builder-preview{
    height: calc(100vh - 140px);
    border-radius: 18px;
    border: 1px solid rgba(15,23,42,.12);
    background: #fff;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,.06);
  }
  .builder-preview iframe{
    width:100%;
    height:100%;
    border:0;
  }

  /* Small warning panel */
  .b-warn{
    background: rgba(226,59,49,.06);
    border: 1px solid rgba(226,59,49,.25);
    color: #7f1d1d;
    border-radius: 14px;
    padding: 10px 12px;
    font-weight: 800;
    font-size: 12px;
    line-height: 1.5;
    margin-top: 12px;
  }
</style>
@endpush
@section('top_actions')
  <a class="btn btn-ghost" href="{{ route('admin.content.index') }}">
    <span class="btn-ico" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M15 18l-6-6 6-6"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"/>
      </svg>
    </span>
    Retour
  </a>
@endsection
@section('content')
<div class="builder-shell" id="builderShell">

  {{-- LEFT PANEL --}}
  <div class="builder-panel" id="builderPanel">

    <div class="b-head">
      <div>
        <h3 class="b-title">Édition visuelle</h3>
        <p class="b-sub">
          Cliquez sur un texte ou une image avec un cadre rouge pour modifier.
          Structure verrouillée.
        </p>
      </div>

      <div class="b-chip" title="Éléments modifiables">
        <span class="dot"></span> CMS
      </div>
    </div>

    <div class="b-row">
      <button class="b-btn b-btn-ghost" id="reloadPreview" type="button">Reload</button>
      <button class="b-btn b-btn-primary" id="saveAll" type="button">Save</button>
      <button class="b-btn" id="clearPending" type="button">Clear</button>
    </div>

    <div class="b-divider"></div>

    {{-- Page selector --}}
    <div class="b-field">
      <label class="b-label">Page à prévisualiser</label>
      <select class="b-select" id="pageSelect">
        <option value="">— Choisir une page —</option>
        <option value="{{ route('home') }}">Accueil</option>
        <option value="{{ route('about') }}">À propos</option>
        <option value="{{ route('services') }}">Services</option>
        <option value="{{ route('contact') }}">Contact</option>
        <option value="{{ route('apply') }}">Postuler</option>
        <option value="{{ route('jobs') }}">Offres d’emploi</option>
        <option value="{{ route('catalogue') }}">Catalogue formation</option>
      </select>
      <div class="b-help">Astuce: toutes les pages ici s’ouvrent automatiquement avec <code>?builder=1</code>.</div>
    </div>

    {{-- Optional URL input --}}
    <div class="b-field">
      <label class="b-label">Ou URL (optionnel)</label>
      <input class="b-input" id="previewUrl" value="{{ $startUrl }}" />
      <div class="b-row" style="margin-top:10px;">
        <button class="b-btn" id="goUrl" type="button">Go URL</button>
      </div>
      <div class="b-help">Utile pour ouvrir une page détail (ex: /offres/{slug}, /catalogue-formation/{slug}).</div>
    </div>

    <div class="b-warn">
      Règle: seuls les éléments avec <code>data-cms-key</code> ou <code>data-cms-img</code> sont modifiables.
      <br>Si un élément ne réagit pas au clic, il n’a pas de clé CMS.
    </div>

    <div class="b-status" id="statusLine">
      Status: Waiting for selection…
      <small id="pendingLine">Pending: 0</small>
    </div>

    {{-- SELECTED --}}
    <div class="b-selected" id="selectedBox" style="display:none;">
      <div class="b-key" id="selectedKey"></div>

      {{-- TEXT EDITOR --}}
      <div class="b-field" id="textEditor" style="display:none;">
        <label class="b-label">Texte</label>
        <textarea class="b-textarea" id="textValue"></textarea>

        <div class="b-row" style="margin-top:10px;">
          <button class="b-btn b-btn-primary" id="applyText" type="button">Apply</button>
        </div>
      </div>

      {{-- IMAGE EDITOR --}}
      <div class="b-field" id="imageEditor" style="display:none;">
        <label class="b-label">Image</label>
        <input class="b-input" style="padding-top:8px;" type="file" id="imageFile" accept="image/*" />
        <div class="b-row" style="margin-top:10px;">
          <button class="b-btn b-btn-primary" id="uploadImage" type="button">Upload</button>
        </div>
      </div>
    </div>

  </div>

  {{-- RESIZER --}}
  <div class="builder-resizer" id="builderResizer" title="Drag to resize"></div>

  {{-- PREVIEW --}}
  <div class="builder-preview">
    <iframe id="siteFrame" src="{{ $startUrl }}"></iframe>
  </div>

</div>

@push('scripts')
<script>
(function(){
  const shell = document.getElementById('builderShell');
  const panel = document.getElementById('builderPanel');
  const resizer = document.getElementById('builderResizer');

  const frame = document.getElementById('siteFrame');
  const pageSelect = document.getElementById('pageSelect');

  const previewUrl = document.getElementById('previewUrl');
  const goUrl = document.getElementById('goUrl');
  const reloadPreview = document.getElementById('reloadPreview');
  const saveAll = document.getElementById('saveAll');
  const clearPending = document.getElementById('clearPending');

  const statusLine = document.getElementById('statusLine');
  const pendingLine = document.getElementById('pendingLine');

  const selectedBox = document.getElementById('selectedBox');
  const selectedKey = document.getElementById('selectedKey');

  const textEditor = document.getElementById('textEditor');
  const textValue = document.getElementById('textValue');
  const applyText = document.getElementById('applyText');

  const imageEditor = document.getElementById('imageEditor');
  const imageFile = document.getElementById('imageFile');
  const uploadImage = document.getElementById('uploadImage');

  let current = null;
  let pending = []; // {key,value}

  function updatePendingUI(){
    pendingLine.textContent = `Pending: ${pending.length}`;
  }

  function ensureBuilderParam(url){
    const u = new URL(url, window.location.origin);
    u.searchParams.set('builder', '1');
    return u.toString();
  }

  function setFrame(url){
    const finalUrl = ensureBuilderParam(url);
    frame.src = finalUrl;
    previewUrl.value = finalUrl;
    statusLine.firstChild.textContent = 'Status: Preview loaded.';
  }

  // init
  try { setFrame(previewUrl.value || "{{ $startUrl }}"); } catch(e){}

  pageSelect.addEventListener('change', () => {
    const v = pageSelect.value;
    if (!v) return;
    setFrame(v);
  });

  goUrl.addEventListener('click', () => {
    if (!previewUrl.value) return;
    setFrame(previewUrl.value);
  });

  reloadPreview.addEventListener('click', () => {
    try {
      frame.contentWindow.location.reload();
      statusLine.firstChild.textContent = 'Status: Preview reloaded.';
    } catch(e){
      alert('Cannot reload preview');
    }
  });

  clearPending.addEventListener('click', () => {
    pending = [];
    updatePendingUI();
    statusLine.firstChild.textContent = 'Status: Pending cleared.';
  });

  // ✅ RECEIVE selection from iframe
  window.addEventListener('message', (e) => {
    if (!e.data) return;

    // accept both formats
    if (e.data.type !== 'CMS_SELECT' && e.data.type !== 'cms_select') return;

    const data = (e.data.type === 'cms_select') ? (e.data.payload || {}) : e.data;

    if (!data || !data.key || !data.kind) return;

    current = data;

    selectedBox.style.display = 'block';
    selectedKey.textContent = current.key;
    statusLine.firstChild.textContent = `Status: Selected ${current.kind} → ${current.key}`;

    if (current.kind === 'text') {
      textEditor.style.display = 'block';
      imageEditor.style.display = 'none';
      textValue.value = (current.text ?? current.value ?? '');
    } else {
      textEditor.style.display = 'none';
      imageEditor.style.display = 'block';
      imageFile.value = '';
    }
  });

  // ✅ APPLY text (live update + pending)
  applyText.addEventListener('click', () => {
    if (!current || current.kind !== 'text') return;

    const val = textValue.value;

    frame.contentWindow.postMessage({
      type:'cms_update_text',
      payload:{ key: current.key, value: val }
    }, '*');

    pending = pending.filter(x => x.key !== current.key);
    pending.push({ key: current.key, value: val });
    updatePendingUI();

    statusLine.firstChild.textContent = `Status: Applied (pending) → ${current.key}`;
  });

  // ✅ UPLOAD image (save instantly via endpoint)
  uploadImage.addEventListener('click', async () => {
    if (!current || current.kind !== 'image') return;
    if (!imageFile.files[0]) return alert('Choose an image first');

    const fd = new FormData();
    fd.append('key', current.key);
    fd.append('image', imageFile.files[0]);
    fd.append('_token', '{{ csrf_token() }}');

    const res = await fetch('{{ route('admin.content.builder.upload') }}', {
      method: 'POST',
      body: fd
    });

    const json = await res.json();
    if (!json.ok) return alert('Upload failed');

    frame.contentWindow.postMessage({
      type:'cms_update_image',
      payload:{ key: current.key, url: json.url }
    }, '*');

    statusLine.firstChild.textContent = `Status: Image uploaded → ${current.key}`;
    alert('Image updated ✅');
  });

  // ✅ SAVE all pending texts
  saveAll.addEventListener('click', async () => {
    if (!pending.length) return alert('Nothing to save');

    const res = await fetch('{{ route('admin.content.builder.save') }}', {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' },
      body: JSON.stringify({ items: pending })
    });

    const json = await res.json();
    if (!json.ok) return alert('Save failed');

    pending = [];
    updatePendingUI();

    statusLine.firstChild.textContent = 'Status: Saved ✅';
    alert('Saved ✅');
  });

  updatePendingUI();

  /* ============================================================
     RESIZE LEFT PANEL (drag)
  ============================================================ */
  let dragging = false;

  resizer.addEventListener('mousedown', (e) => {
    dragging = true;
    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
  });

  window.addEventListener('mouseup', () => {
    dragging = false;
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
  });

  window.addEventListener('mousemove', (e) => {
    if (!dragging) return;

    const min = 320;
    const max = 620;

    const left = shell.getBoundingClientRect().left;
    let w = e.clientX - left;

    w = Math.max(min, Math.min(max, w));

    shell.style.gridTemplateColumns = `${w}px 10px 1fr`;
  });

})();
</script>
@endpush
@endsection
