<script>
document.addEventListener('DOMContentLoaded', function () {
  const selector = '.admin-main select:not([multiple]):not([data-native-select]), .portal-main select:not([multiple]):not([data-native-select])';
  const enhanced = new WeakSet();
  let selectUid = 0;
  let suppressOutsideCloseUntil = 0;
  const layerSelector = [
    '.panel',
    '.panel-body',
    '.portal-card',
    '.admin-card',
    '.ui-card',
    '.ui-table-shell',
    '.table-wrap',
    '.cv-table-wrap',
    '.cv-filters-panel',
    '.cv-table-panel',
    '.portal-content'
  ].join(',');

  function suppressOutsideClose(duration) {
    suppressOutsideCloseUntil = Date.now() + (duration || 180);
  }

  function shouldSuppressOutsideClose() {
    return Date.now() < suppressOutsideCloseUntil;
  }

  function refreshLayerMarks() {
    document.querySelectorAll('.rhs-select-layer-open').forEach(function (element) {
      element.classList.remove('rhs-select-layer-open');
    });

    document.querySelectorAll('.rhs-select.is-open').forEach(function (wrapper) {
      let node = wrapper.parentElement;

      while (node && node !== document.body) {
        if (node.matches && node.matches(layerSelector)) {
          node.classList.add('rhs-select-layer-open');
        }

        node = node.parentElement;
      }
    });
  }

  function closeAll(except) {
    document.querySelectorAll('.rhs-select.is-open').forEach(function (wrapper) {
      if (wrapper !== except) {
        wrapper.classList.remove('is-open');
        wrapper.querySelector('.rhs-select-button')?.setAttribute('aria-expanded', 'false');
        clearMenuPosition(wrapper);
      }
    });

    refreshLayerMarks();
  }

  function isInsideSelectUi(target, event) {
    const path = typeof event?.composedPath === 'function' ? event.composedPath() : [];
    const pathContainsSelect = path.some(function (node) {
      return node?.classList && (
        node.classList.contains('rhs-select') || node.classList.contains('rhs-select-menu')
      );
    });

    return pathContainsSelect || !!(target && target.closest && (
      target.closest('.rhs-select') || target.closest('.rhs-select-menu')
    ));
  }

  function menuFor(wrapper) {
    return wrapper.querySelector('.rhs-select-menu')
      || document.querySelector('.rhs-select-menu[data-rhs-select-owner="' + wrapper.dataset.rhsSelectId + '"]');
  }

  function clearMenuPosition(wrapper) {
    const menu = menuFor(wrapper);

    if (!menu) {
      return;
    }

    menu.style.removeProperty('--rhs-select-menu-left');
    menu.style.removeProperty('--rhs-select-menu-top');
    menu.style.removeProperty('--rhs-select-menu-width');
    menu.style.removeProperty('--rhs-select-menu-max-height');
    menu.style.removeProperty('position');
    menu.classList.remove('is-portaled');

    if (menu.parentElement !== wrapper) {
      wrapper.appendChild(menu);
    }
  }

  function selectedLabel(select) {
    const option = select.options[select.selectedIndex];
    return option ? option.textContent.trim() : '';
  }

  function chooseOption(select, wrapper, option) {
    if (option.disabled) {
      return;
    }

    select.value = option.value;
    select.dispatchEvent(new Event('change', { bubbles: true }));
    syncFromSelect(select, wrapper);
    closeAll();
  }

  function buildOption(select, wrapper, option) {
    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'rhs-select-option';
    item.setAttribute('role', 'option');
    item.dataset.value = option.value;
    item.textContent = option.textContent.trim();

    if (option.disabled) {
      item.disabled = true;
      item.classList.add('is-disabled');
    }

    if (option.selected) {
      item.classList.add('is-selected');
      item.setAttribute('aria-selected', 'true');
    }

    item.addEventListener('pointerdown', function (event) {
      event.preventDefault();
      event.stopPropagation();
      suppressOutsideClose();
      chooseOption(select, wrapper, option);
    });

    item.addEventListener('mousedown', function (event) {
      event.preventDefault();
      event.stopPropagation();
      suppressOutsideClose();
    });

    item.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
    });

    return item;
  }

  function renderOptions(select, wrapper) {
    const menu = menuFor(wrapper);
    menu.innerHTML = '';

    Array.from(select.options).forEach(function (option) {
      menu.appendChild(buildOption(select, wrapper, option));
    });
  }

  function syncFromSelect(select, wrapper) {
    wrapper.querySelector('.rhs-select-value').textContent = selectedLabel(select);
    wrapper.classList.toggle('is-disabled', select.disabled);
    wrapper.querySelector('.rhs-select-button').disabled = select.disabled;

    menuFor(wrapper)?.querySelectorAll('.rhs-select-option').forEach(function (item) {
      const isSelected = item.dataset.value === select.value;
      item.classList.toggle('is-selected', isSelected);
      item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
    });
  }

  function positionMenu(wrapper) {
    const button = wrapper.querySelector('.rhs-select-button');
    const menu = menuFor(wrapper);

    if (!button || !menu || !wrapper.classList.contains('is-open')) {
      return;
    }

    if (menu.parentElement !== document.body) {
      document.body.appendChild(menu);
    }

    menu.classList.add('is-portaled');
    menu.style.position = 'fixed';

    const rect = button.getBoundingClientRect();
    const viewportGap = 12;
    const belowSpace = window.innerHeight - rect.bottom - viewportGap;
    const aboveSpace = rect.top - viewportGap;
    const openAbove = belowSpace < 220 && aboveSpace > belowSpace;
    const maxHeight = Math.max(160, Math.min(340, (openAbove ? aboveSpace : belowSpace) - 8));
    const top = openAbove
      ? Math.max(viewportGap, rect.top - maxHeight - 8)
      : Math.min(window.innerHeight - viewportGap, rect.bottom + 8);

    wrapper.classList.toggle('is-open-above', openAbove);
    menu.style.setProperty('--rhs-select-menu-left', Math.round(rect.left) + 'px');
    menu.style.setProperty('--rhs-select-menu-top', Math.round(top) + 'px');
    menu.style.setProperty('--rhs-select-menu-width', Math.round(rect.width) + 'px');
    menu.style.setProperty('--rhs-select-menu-max-height', Math.round(maxHeight) + 'px');
  }

  function enhance(select) {
    if (enhanced.has(select) || select.closest('.rhs-select')) {
      return;
    }

    enhanced.add(select);
    select.classList.add('rhs-select-source');

    const wrapper = document.createElement('div');
    wrapper.className = 'rhs-select';
    wrapper.dataset.rhsSelectId = 'rhs-select-' + (++selectUid);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'rhs-select-button';
    button.setAttribute('aria-haspopup', 'listbox');
    button.setAttribute('aria-expanded', 'false');

    const value = document.createElement('span');
    value.className = 'rhs-select-value';

    const caret = document.createElement('span');
    caret.className = 'rhs-select-caret';
    caret.setAttribute('aria-hidden', 'true');
    caret.innerHTML = '<svg viewBox="0 0 24 24" fill="none"><path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    const menu = document.createElement('div');
    menu.className = 'rhs-select-menu';
    menu.setAttribute('role', 'listbox');
    menu.dataset.rhsSelectOwner = wrapper.dataset.rhsSelectId;

    ['pointerdown', 'mousedown', 'click'].forEach(function (eventName) {
      menu.addEventListener(eventName, function (event) {
        if (eventName !== 'click') {
          event.preventDefault();
        }

        event.stopPropagation();
      });
    });

    menu.addEventListener('wheel', function (event) {
      event.stopPropagation();
    }, { passive: true });

    button.appendChild(value);
    button.appendChild(caret);
    wrapper.appendChild(button);
    wrapper.appendChild(menu);
    select.insertAdjacentElement('afterend', wrapper);

    renderOptions(select, wrapper);
    syncFromSelect(select, wrapper);

    button.addEventListener('pointerdown', function (event) {
      event.stopPropagation();
      suppressOutsideClose();
    });

    button.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();

      if (select.disabled) {
        return;
      }

      const willOpen = !wrapper.classList.contains('is-open');
      closeAll(wrapper);
      wrapper.classList.toggle('is-open', willOpen);
      button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      if (willOpen) {
        positionMenu(wrapper);
        window.requestAnimationFrame(function () {
          positionMenu(wrapper);
        });
      } else {
        clearMenuPosition(wrapper);
      }
      refreshLayerMarks();
    });

    select.addEventListener('change', function () {
      renderOptions(select, wrapper);
      syncFromSelect(select, wrapper);
    });
  }

  document.querySelectorAll(selector).forEach(enhance);

  document.addEventListener('pointerdown', function (event) {
    if (shouldSuppressOutsideClose()) {
      return;
    }

    if (!isInsideSelectUi(event.target, event)) {
      closeAll();
    }
  });

  document.addEventListener('click', function (event) {
    if (shouldSuppressOutsideClose()) {
      return;
    }

    if (!isInsideSelectUi(event.target, event)) {
      closeAll();
    }
  });

  window.addEventListener('resize', function () {
    document.querySelectorAll('.rhs-select.is-open').forEach(positionMenu);
  });

  let scrollFrame = null;
  window.addEventListener('scroll', function (event) {
    if (event.target && event.target.closest && event.target.closest('.rhs-select-menu')) {
      return;
    }

    if (scrollFrame) {
      window.cancelAnimationFrame(scrollFrame);
    }

    scrollFrame = window.requestAnimationFrame(function () {
      document.querySelectorAll('.rhs-select.is-open').forEach(positionMenu);
      scrollFrame = null;
    });
  }, true);

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeAll();
    }
  });
});
</script>
