/* ==========================================================================
   CMS Inline Editor (runs INSIDE the iframe) - Builder mode only
   Editable items:
   - Text:  data-cms-key="some.key"
   - Image: data-cms-img="some.key"   (for <img> OR background image elements)
   ========================================================================== */

(function () {
  const isBuilder = new URLSearchParams(window.location.search).get('builder') === '1';
  if (!isBuilder) return;

  console.log('%c cms-inline.js running (builder mode)', 'color:#22c55e;font-weight:900');

  // -----------------------------
  // Styles for overlay highlights
  // -----------------------------
  const style = document.createElement('style');
  style.innerHTML = `
    [data-cms-key], [data-cms-img] { outline: 0 !important; }

    .cms-hover-outline{
      outline: 2px solid rgba(226,59,49,.85) !important;
      outline-offset: 2px !important;
    }

    .cms-fixed-outline{
      outline: 3px solid rgba(226,59,49,1) !important;
      outline-offset: 2px !important;
    }

    .cms-badge{
      position:absolute;
      z-index: 2147483647;
      background: rgba(226,59,49,.95);
      color:#fff;
      font: 700 11px/1.2 system-ui, -apple-system, Segoe UI, Roboto, Arial;
      padding: 4px 6px;
      border-radius: 8px;
      pointer-events:none;
      transform: translateY(-100%);
      margin-top: -6px;
      box-shadow: 0 8px 18px rgba(0,0,0,.18);
      white-space:nowrap;
    }
  `;
  document.head.appendChild(style);

  let lastSelectedEl = null;
  let badgeEl = null;

  function clearSelected() {
    if (lastSelectedEl) lastSelectedEl.classList.remove('cms-fixed-outline');
    lastSelectedEl = null;
    if (badgeEl) badgeEl.remove();
    badgeEl = null;
  }

  function makeBadge(target, text) {
    if (badgeEl) badgeEl.remove();
    badgeEl = document.createElement('div');
    badgeEl.className = 'cms-badge';
    badgeEl.textContent = text;

    document.body.appendChild(badgeEl);

    const rect = target.getBoundingClientRect();
    // position badge near top-left of element (inside viewport)
    const left = Math.max(8, rect.left + window.scrollX);
    const top  = Math.max(8, rect.top + window.scrollY);
    badgeEl.style.left = left + 'px';
    badgeEl.style.top  = top + 'px';
  }

  // Find closest editable element
  function findEditable(el) {
    if (!el) return null;
    return el.closest('[data-cms-key], [data-cms-img]');
  }

  // Determine if element is an "image" editable
  function getKindAndKey(el) {
    const imgKey = el.getAttribute('data-cms-img');
    const textKey = el.getAttribute('data-cms-key');

    if (imgKey) return { kind: 'image', key: imgKey };
    if (textKey) return { kind: 'text', key: textKey };

    return null;
  }

  function extractImageUrl(el) {
    // <img src="...">
    if (el.tagName && el.tagName.toLowerCase() === 'img') {
      return el.getAttribute('src') || '';
    }

    // background-image: url(...)
    const bg = getComputedStyle(el).backgroundImage || '';
    const match = bg.match(/url\(["']?(.*?)["']?\)/i);
    return match ? match[1] : '';
  }

  function extractText(el) {
    // keep it simple: textContent
    return (el.textContent || '').trim();
  }

  // Send selection to parent (Builder page)
  function postSelect(payload) {
    // important: send to parent window
    window.parent.postMessage(
      {
        type: 'CMS_SELECT',
        kind: payload.kind,
        key: payload.key,
        text: payload.text ?? null,
        img: payload.img ?? null,
        tag: payload.tag ?? null,
        href: payload.href ?? null
      },
      '*'
    );
  }

  // -----------------------------
  // Hover highlight (optional)
  // -----------------------------
  document.addEventListener('mousemove', (e) => {
    const t = findEditable(e.target);
    document.querySelectorAll('.cms-hover-outline').forEach(x => x.classList.remove('cms-hover-outline'));
    if (t && t !== lastSelectedEl) t.classList.add('cms-hover-outline');
  }, true);

  // -----------------------------
  // CLICK / POINTERDOWN selection
  // -----------------------------
  document.addEventListener('pointerdown', (e) => {
    const t = findEditable(e.target);
    if (!t) return;

    // Stop link clicks / slider handling in builder
    e.preventDefault();
    e.stopPropagation();

    // lock selection highlight
    clearSelected();
    lastSelectedEl = t;
    t.classList.add('cms-fixed-outline');

    const info = getKindAndKey(t);
    if (!info) return;

    if (info.kind === 'text') {
      const payload = {
        kind: 'text',
        key: info.key,
        text: extractText(t),
        tag: (t.tagName || '').toLowerCase(),
        href: t.getAttribute('href') || null
      };
      makeBadge(t, `TEXT • ${info.key}`);
      postSelect(payload);
      return;
    }

    if (info.kind === 'image') {
      const payload = {
        kind: 'image',
        key: info.key,
        img: extractImageUrl(t),
        tag: (t.tagName || '').toLowerCase()
      };
      makeBadge(t, `IMAGE • ${info.key}`);
      postSelect(payload);
      return;
    }
  }, true);

  // Allow ESC to clear selection
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') clearSelected();
  });

  // -----------------------------
  // Receive updates from Builder
  // -----------------------------
  window.addEventListener('message', (e) => {
    if (!e.data) return;

    // Update text
    if (e.data.type === 'cms_update_text') {
      const { key, value } = e.data.payload || {};
      if (!key) return;

      const el = document.querySelector(`[data-cms-key="${CSS.escape(key)}"]`);
      if (!el) return;

      el.textContent = value ?? '';
      return;
    }

    // Update image
    if (e.data.type === 'cms_update_image') {
      const { key, url } = e.data.payload || {};
      if (!key || !url) return;

      const el = document.querySelector(`[data-cms-img="${CSS.escape(key)}"]`);
      if (!el) return;

      // <img>
      if (el.tagName && el.tagName.toLowerCase() === 'img') {
        el.setAttribute('src', url);
        return;
      }

      // background image
      el.style.backgroundImage = `url("${url}")`;
      return;
    }
  });

})();
