(function () {
  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
      return;
    }

    callback();
  }

  function markAnimatedElements() {
    const selectors = [
      '.admin-content > *',
      '.portal-content > *',
      '.dash-kpi',
      '.dash-action',
      '.panel',
      '.admin-card',
      '.portal-card',
      '.portal-record',
      '.cv-filters-panel',
      '.cv-table-panel',
      '.ui-table-shell',
      '.ui-progress-card',
      '.contact-card',
      '.contact-form-card',
      '.contact-map',
      '.contact-mini-cta',
      '.service-card',
      '.service-detail-grid',
      '.service-points-card',
      '.formation-card',
      '.job-card',
      '.catalogue-cta',
      '.why-card',
      '.about-value-card',
      '.about-page section > *',
      '.services-page [data-animate]',
      '.services-page [data-reveal]',
      '.contact-page [data-animate]',
      '.contact-page [data-reveal]',
      '.apply-page [data-animate]',
      '.apply-page [data-reveal]',
      '.jobs-page [data-animate]',
      '.jobs-page [data-reveal]',
      '.job-detail-page [data-animate]',
      '.job-detail-page [data-reveal]',
      '.formation-page [data-animate]',
      '.formation-page [data-reveal]',
      '.home-section > *',
      '.hero-content > *'
    ];

    document.querySelectorAll(selectors.join(',')).forEach(function (element, index) {
      if (element.closest('.admin-sidebar, .portal-sidebar')) {
        return;
      }

      if (!element.hasAttribute('data-rhs-animate')) {
        element.setAttribute('data-rhs-animate', '');
      }

      element.style.setProperty('--rhs-delay', Math.min(index % 8, 7) * 45 + 'ms');
    });

    document.querySelectorAll('[data-animate], [data-reveal]').forEach(function (element, index) {
      if (!element.hasAttribute('data-rhs-animate')) {
        element.setAttribute('data-rhs-animate', '');
      }

      element.style.setProperty('--rhs-delay', Math.min(index % 8, 7) * 55 + 'ms');
    });
  }

  function revealAnimatedElements() {
    const elements = document.querySelectorAll('[data-rhs-animate]');

    if (!elements.length) {
      return;
    }

    if (!('IntersectionObserver' in window)) {
      elements.forEach(function (element) {
        element.classList.add('is-visible');
      });
      return;
    }

    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, {
      rootMargin: '0px 0px -8% 0px',
      threshold: 0.08
    });

    elements.forEach(function (element) {
      observer.observe(element);
    });

    window.setTimeout(function () {
      elements.forEach(function (element) {
        element.classList.add('is-visible');
      });
    }, 900);
  }

  function enhanceHoverTargets() {
    const selectors = [
      '.dash-kpi',
      '.dash-action',
      '.admin-card',
      '.portal-card',
      '.portal-record',
      '.contact-card',
      '.service-card',
      '.formation-card',
      '.job-card'
    ];

    document.querySelectorAll(selectors.join(',')).forEach(function (element) {
      element.classList.add('rhs-hover-lift');
    });
  }

  function enhanceButtons() {
    const selectors = [
      '.btn',
      '.admin-btn',
      '.cv-icon-btn',
      '.portal-header-link',
      '.btn-primary',
      '.btn-red',
      '.btn-outline-red',
      'button[type="submit"]'
    ];

    document.querySelectorAll(selectors.join(',')).forEach(function (button) {
      button.classList.add('rhs-ripple');

      button.addEventListener('pointerdown', function (event) {
        const rect = button.getBoundingClientRect();

        button.style.setProperty('--ripple-x', event.clientX - rect.left + 'px');
        button.style.setProperty('--ripple-y', event.clientY - rect.top + 'px');
        button.classList.remove('is-rippling');

        window.requestAnimationFrame(function () {
          button.classList.add('is-rippling');
        });
      });

      button.addEventListener('animationend', function () {
        button.classList.remove('is-rippling');
      });
    });
  }

  function initSortableTables(root) {
    const scope = root || document;
    const tables = scope.querySelectorAll('.admin-main table, .portal-main table');

    const normalizeValue = function (value) {
      return String(value || '')
        .replace(/\s+/g, ' ')
        .replace(/\u00a0/g, ' ')
        .trim();
    };

    const parseSortableValue = function (value) {
      const text = normalizeValue(value);
      const numeric = text
        .replace(/\s/g, '')
        .replace(/%/g, '')
        .replace(',', '.')
        .replace(/[^\d.-]/g, '');

      if (numeric !== '' && !Number.isNaN(Number(numeric)) && /\d/.test(text)) {
        return { type: 'number', value: Number(numeric) };
      }

      const dateMatch = text.match(/^(\d{1,2})[\/.-](\d{1,2})(?:[\/.-](\d{2,4}))?(?:\s+(\d{1,2}):(\d{2}))?/);
      if (dateMatch) {
        const year = dateMatch[3] ? Number(String(dateMatch[3]).padStart(4, '20')) : new Date().getFullYear();
        const date = new Date(
          year,
          Number(dateMatch[2]) - 1,
          Number(dateMatch[1]),
          Number(dateMatch[4] || 0),
          Number(dateMatch[5] || 0)
        );

        if (!Number.isNaN(date.getTime())) {
          return { type: 'number', value: date.getTime() };
        }
      }

      return { type: 'text', value: text.toLocaleLowerCase('fr-FR') };
    };

    const cellValue = function (row, index) {
      const cell = row.children[index];
      if (!cell) return '';

      const sortValue = cell.getAttribute('data-sort-value');
      return sortValue !== null ? sortValue : cell.textContent;
    };

    tables.forEach(function (table) {
      if (table.dataset.rhsSortable === '1' || table.hasAttribute('data-no-sort')) {
        return;
      }

      const head = table.tHead;
      const body = table.tBodies && table.tBodies[0];
      if (!head || !body) return;

      const headers = Array.from(head.querySelectorAll('th'));
      const rows = Array.from(body.querySelectorAll('tr')).filter(function (row) {
        return !row.querySelector('td[colspan]');
      });

      if (headers.length < 2 || rows.length < 2) {
        return;
      }

      table.dataset.rhsSortable = '1';
      table.classList.add('rhs-sortable-table');

      headers.forEach(function (header, index) {
        const isAction = header.classList.contains('th-actions')
          || /actions?/i.test(header.textContent || '')
          || index >= headers.length - 1 && header.closest('table')?.querySelector('tbody tr td:last-child .btn, tbody tr td:last-child button, tbody tr td:last-child form');

        if (isAction || header.hasAttribute('data-no-sort')) {
          header.classList.add('rhs-no-sort');
          return;
        }

        header.setAttribute('tabindex', '0');
        header.setAttribute('role', 'button');
        header.setAttribute('aria-sort', 'none');
        header.classList.add('rhs-sortable-head');

        const sortColumn = function () {
          const currentDirection = table.dataset.rhsSortIndex === String(index)
            ? table.dataset.rhsSortDirection
            : '';
          const nextDirection = currentDirection === 'asc' ? 'desc' : 'asc';

          headers.forEach(function (item) {
            item.classList.remove('is-sort-asc', 'is-sort-desc');
            item.setAttribute('aria-sort', 'none');
          });

          table.dataset.rhsSortIndex = String(index);
          table.dataset.rhsSortDirection = nextDirection;
          header.classList.add(nextDirection === 'asc' ? 'is-sort-asc' : 'is-sort-desc');
          header.setAttribute('aria-sort', nextDirection === 'asc' ? 'ascending' : 'descending');

          const sortedRows = Array.from(body.querySelectorAll('tr')).filter(function (row) {
            return !row.querySelector('td[colspan]');
          }).sort(function (leftRow, rightRow) {
            const left = parseSortableValue(cellValue(leftRow, index));
            const right = parseSortableValue(cellValue(rightRow, index));
            let result = 0;

            if (left.type === 'number' && right.type === 'number') {
              result = left.value - right.value;
            } else {
              result = String(left.value).localeCompare(String(right.value), 'fr', {
                numeric: true,
                sensitivity: 'base'
              });
            }

            return nextDirection === 'asc' ? result : -result;
          });

          sortedRows.forEach(function (row) {
            body.appendChild(row);
          });
        };

        header.addEventListener('click', function (event) {
          if (event.target.closest('a, button, input, select, textarea')) return;
          sortColumn();
        });

        header.addEventListener('keydown', function (event) {
          if (event.key !== 'Enter' && event.key !== ' ') return;
          event.preventDefault();
          sortColumn();
        });
      });
    });
  }

  function enhanceFileCards() {
    document.querySelectorAll('.rhs-file-card-input').forEach(function (input) {
      const label = document.querySelector('label[for="' + input.id + '"].rhs-file-card');

      if (!label) {
        return;
      }

      const title = label.querySelector('strong');
      const helper = label.querySelector('small');
      const originalTitle = title ? title.textContent : '';
      const originalHelper = helper ? helper.textContent : '';

      input.addEventListener('change', function () {
        const files = Array.from(input.files || []);

        if (!files.length) {
          if (title) title.textContent = originalTitle;
          if (helper) helper.textContent = originalHelper;
          return;
        }

        if (title) {
          title.textContent = files.length === 1 ? files[0].name : files.length + ' fichier(s) selectionne(s)';
        }

        if (helper) {
          const totalSize = files.reduce(function (sum, file) {
            return sum + file.size;
          }, 0);
          helper.textContent = totalSize > 1048576
            ? (totalSize / 1048576).toFixed(1) + ' Mo'
            : Math.max(1, Math.round(totalSize / 1024)) + ' Ko';
        }
      });
    });
  }

  function animateDashboardNumbers() {
    const numberSelectors = [
      '.dash-kpi-value',
      '.portal-kpi',
      '.dash-chart-value'
    ];

    document.querySelectorAll(numberSelectors.join(',')).forEach(function (element) {
      const raw = (element.textContent || '').replace(/\s+/g, '').replace(',', '.');
      const value = Number.parseFloat(raw);

      if (!Number.isFinite(value) || value < 1 || raw.match(/[^\d.]/)) {
        return;
      }

      const finalText = element.textContent;
      const duration = 720;
      const start = performance.now();

      function frame(now) {
        const progress = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(value * eased);

        element.textContent = current.toLocaleString('fr-FR');

        if (progress < 1) {
          window.requestAnimationFrame(frame);
          return;
        }

        element.textContent = finalText;
      }

      window.requestAnimationFrame(frame);
    });
  }

  function renderDashboardCharts(scope) {
    const palette = ['#ef233c', '#f97316', '#2563eb', '#10b981', '#8b5cf6', '#f59e0b', '#0f172a'];
    const root = scope && scope.querySelectorAll ? scope : document;

    function escapeHtml(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function normalizeData(rawData) {
      return rawData
        .map(function (item, index) {
          return {
            label: item.label || 'Element ' + (index + 1),
            value: Number(item.value || 0),
            secondary: Number(item.secondary || 0),
            third: Number(item.third || 0),
            total: Number(item.total || item.value || 0),
            color: item.color || palette[index % palette.length]
          };
        })
        .filter(function (item) {
          return Number.isFinite(item.value) && item.value >= 0;
        });
    }

    function legendMarkup(data) {
      return '<div class="rhs-chart-legend">' + data.map(function (item, index) {
        return '<button type="button" class="rhs-chart-legend-item" data-chart-index="' + index + '">' +
          '<span class="rhs-chart-dot" style="--chart-color:' + item.color + '"></span>' +
          '<span class="rhs-chart-legend-label">' + escapeHtml(item.label) + '</span>' +
          '<strong>' + item.value.toLocaleString('fr-FR') + '</strong>' +
        '</button>';
      }).join('') + '</div>';
    }

    function renderChartDetails(container, index, sourceTarget) {
      let data = [];

      try {
        data = normalizeData(JSON.parse(container.getAttribute('data-chart') || '[]'));
      } catch (error) {
        data = [];
      }

      const item = data[index] || {
        label: (sourceTarget.getAttribute('data-chart-label') || sourceTarget.textContent || 'Detail').split(':')[0].trim(),
        value: Number((sourceTarget.textContent || '').match(/\d+/)?.[0] || 0),
        secondary: 0,
        third: 0,
        total: 0,
        color: '#ef233c'
      };
      const total = data.reduce(function (sum, row) {
        return sum + Number(row.total || row.value || 0);
      }, 0);
      const value = Number(item.total || item.value || 0);
      const share = total > 0 ? Math.round((value / total) * 100) : 0;
      const sorted = data.slice().sort(function (a, b) {
        return Number(b.total || b.value || 0) - Number(a.total || a.value || 0);
      });
      const rank = Math.max(1, sorted.findIndex(function (row) {
        return row.label === item.label && Number(row.value) === Number(item.value);
      }) + 1);
      const unit = container.getAttribute('data-unit') || 'element';
      const primaryLabel = container.getAttribute('data-primary-label') || 'Valeur';
      const secondaryLabel = container.getAttribute('data-secondary-label') || 'Secondaire';
      const thirdLabel = container.getAttribute('data-third-label') || 'Troisieme';
      const card = container.closest('.dash-card, .portal-card') || container.parentElement;
      let panel = card.querySelector('.rhs-chart-drilldown');

      if (!panel) {
        panel = document.createElement('section');
        panel.className = 'rhs-chart-drilldown';
        card.appendChild(panel);
      }

      panel.innerHTML =
        '<div class="rhs-chart-drilldown-head">' +
          '<div><span>Donnees liees</span><strong>' + escapeHtml(item.label) + '</strong></div>' +
          '<button type="button" aria-label="Fermer les details">x</button>' +
        '</div>' +
        '<div class="rhs-chart-drilldown-grid">' +
          '<div><span>Total</span><strong>' + value.toLocaleString('fr-FR') + '</strong><small>' + escapeHtml(unit) + '</small></div>' +
          '<div><span>Part</span><strong>' + share + '%</strong><small>du graphe</small></div>' +
          '<div><span>Rang</span><strong>#' + rank + '</strong><small>sur ' + data.length.toLocaleString('fr-FR') + '</small></div>' +
        '</div>' +
        '<div class="rhs-chart-drilldown-list">' +
          '<div><span>' + escapeHtml(primaryLabel) + '</span><strong>' + Number(item.value || 0).toLocaleString('fr-FR') + '</strong></div>' +
          (Number(item.secondary || 0) > 0 ? '<div><span>' + escapeHtml(secondaryLabel) + '</span><strong>' + Number(item.secondary).toLocaleString('fr-FR') + '</strong></div>' : '') +
          (Number(item.third || 0) > 0 ? '<div><span>' + escapeHtml(thirdLabel) + '</span><strong>' + Number(item.third).toLocaleString('fr-FR') + '</strong></div>' : '') +
        '</div>';

      panel.querySelector('button')?.addEventListener('click', function () {
        panel.remove();
        container.removeAttribute('data-selected-index');
      });

      panel.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function bindChartInteractions(container) {
      const tooltip = container.querySelector('.rhs-chart-tooltip');
      const targets = container.querySelectorAll('[data-chart-index]');

      targets.forEach(function (target) {
        const index = target.getAttribute('data-chart-index');

        target.addEventListener('mouseenter', function () {
          container.setAttribute('data-active-index', index);
          container.classList.add('is-hovering');

          if (tooltip) {
            tooltip.textContent = target.getAttribute('data-chart-label') || target.textContent.trim();
          }
        });

        target.addEventListener('mouseleave', function () {
          container.classList.remove('is-hovering');
        });

        target.addEventListener('click', function () {
          if (container.getAttribute('data-selected-index') === index) {
            container.removeAttribute('data-selected-index');
            (container.closest('.dash-card, .portal-card') || container.parentElement)
              .querySelector('.rhs-chart-drilldown')
              ?.remove();
            return;
          }

          container.setAttribute('data-selected-index', index);
          renderChartDetails(container, Number(index), target);
        });
      });
    }

    function animateChartWhenVisible(container) {
      if (container.dataset.chartAnimationReady === '1') {
        return;
      }

      const activate = function () {
        container.dataset.chartAnimationReady = '1';
        container.classList.add('is-chart-visible');
      };

      if (!('IntersectionObserver' in window)) {
        activate();
        return;
      }

      const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting || entry.intersectionRatio < 0.5) return;
          activate();
          observer.unobserve(entry.target);
        });
      }, {
        rootMargin: '0px',
        threshold: [0, 0.5]
      });

      observer.observe(container);
    }

    function renderBar(container, data) {
      const max = Math.max.apply(null, data.map(function (item) { return item.value; }).concat([1]));
      const rows = data.map(function (item, index) {
        const width = Math.max(4, Math.round((item.value / max) * 100));

        return '<button type="button" class="rhs-chart-bar-row" data-chart-index="' + index + '" data-chart-label="' + escapeHtml(item.label + ' : ' + item.value) + '">' +
          '<span class="rhs-chart-bar-label">' + escapeHtml(item.label) + '</span>' +
          '<span class="rhs-chart-bar-track"><span style="--bar-width:' + width + '%;--chart-color:' + item.color + ';--chart-delay:' + (index * 260) + 'ms"></span></span>' +
          '<strong>' + item.value.toLocaleString('fr-FR') + '</strong>' +
        '</button>';
      }).join('');

      container.innerHTML = '<div class="rhs-chart-tooltip" role="status"></div><div class="rhs-chart-bars">' + rows + '</div>';
      bindChartInteractions(container);
      animateChartWhenVisible(container);
    }

    function renderDonut(container, data) {
      const total = data.reduce(function (sum, item) { return sum + item.value; }, 0);
      let offset = 0;
      const radius = 44;
      const circumference = 2 * Math.PI * radius;

      const circles = data.map(function (item, index) {
        const portion = total > 0 ? item.value / total : 0;
        const dash = portion * circumference;
        const circle = '<circle class="rhs-chart-donut-segment" data-chart-index="' + index + '" data-chart-label="' + escapeHtml(item.label + ' : ' + item.value) + '" cx="60" cy="60" r="' + radius + '" fill="none" stroke="' + item.color + '" stroke-width="14" stroke-linecap="round" stroke-dasharray="' + dash + ' ' + (circumference - dash) + '" stroke-dashoffset="' + (-offset) + '" style="--chart-delay:' + (index * 280) + 'ms"></circle>';
        offset += dash;
        return circle;
      }).join('');

      container.innerHTML =
        '<div class="rhs-chart-tooltip" role="status"></div>' +
        '<div class="rhs-chart-donut-wrap">' +
          '<svg class="rhs-chart-donut" viewBox="0 0 120 120" role="img" aria-label="Graphique interactif">' +
            '<circle cx="60" cy="60" r="' + radius + '" fill="none" stroke="#eef2f7" stroke-width="14"></circle>' +
            circles +
            '<text x="60" y="56" text-anchor="middle" class="rhs-chart-total">' + total.toLocaleString('fr-FR') + '</text>' +
            '<text x="60" y="75" text-anchor="middle" class="rhs-chart-total-label">total</text>' +
          '</svg>' +
          legendMarkup(data) +
        '</div>';
      bindChartInteractions(container);
      animateChartWhenVisible(container);
    }

    function renderShowcase(container, data) {
      const unit = container.getAttribute('data-unit') || 'item';
      const pickerLabel = container.getAttribute('data-picker-label') || 'Choisir';
      const searchPlaceholder = container.getAttribute('data-search-placeholder') || 'Rechercher';
      let activeIndex = 0;

      function closeAllPickers() {
        document.querySelectorAll('.rhs-showcase-picker.is-open').forEach(function (picker) {
          picker.classList.remove('is-open');
          const toggle = picker.querySelector('.rhs-showcase-picker-toggle');
          if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
          }
        });
      }

      function draw(selectedIndex) {
      activeIndex = Math.max(0, Math.min(selectedIndex || 0, data.length - 1));
      const selected = data[activeIndex] || data[0];
      const remainingTop = data.filter(function (_item, index) { return index !== activeIndex; }).slice(0, 7);
      const topItems = [selected].concat(remainingTop).filter(Boolean);
      const total = data.reduce(function (sum, item) { return sum + item.value; }, 0);
      const max = Math.max.apply(null, topItems.map(function (item) { return item.value; }).concat([1]));
      const tallest = 92;
      const columns = topItems.map(function (item, index) {
        const height = Math.max(12, Math.round((item.value / max) * tallest));
        const percent = total > 0 ? Math.round((item.value / total) * 100) : 0;

        return '<button type="button" class="rhs-showcase-column" data-chart-index="' + index + '" data-chart-label="' + escapeHtml(item.label + ' : ' + item.value + ' ' + unit) + '" style="--chart-color:' + item.color + ';--bar-height:' + height + 'px">' +
          '<span class="rhs-showcase-column-value">' + item.value.toLocaleString('fr-FR') + '</span>' +
          '<span class="rhs-showcase-column-bar" style="--chart-delay:' + (index * 280) + 'ms"></span>' +
          '<span class="rhs-showcase-column-label">' + escapeHtml(item.label) + '</span>' +
          '<span class="rhs-showcase-column-percent">' + percent + '%</span>' +
        '</button>';
      }).join('');

      const lead = topItems[0] || { label: '-', value: 0, color: palette[0] };
      const second = topItems[1] || { label: '-', value: 0, color: palette[1] };
      const third = topItems[2] || { label: '-', value: 0, color: palette[2] };
      const leadShare = total > 0 ? Math.round((lead.value / total) * 100) : 0;
      const sparkPoints = topItems.map(function (item, index) {
        const x = topItems.length <= 1 ? 0 : Math.round((index / (topItems.length - 1)) * 100);
        const y = 52 - Math.round((item.value / max) * 46);

        return x + ',' + y;
      }).join(' ');

      container.innerHTML =
        '<div class="rhs-chart-tooltip" role="status"></div>' +
        '<div class="rhs-showcase-toolbar">' +
          '<div class="rhs-showcase-picker">' +
            '<span>' + escapeHtml(pickerLabel) + '</span>' +
            '<button type="button" class="rhs-showcase-picker-toggle" aria-expanded="false">' +
              '<strong>' + escapeHtml(selected.label) + '</strong>' +
              '<small>' + selected.value.toLocaleString('fr-FR') + '</small>' +
            '</button>' +
            '<div class="rhs-showcase-picker-menu">' +
              '<input type="search" class="rhs-showcase-picker-search" placeholder="' + escapeHtml(searchPlaceholder) + '">' +
              '<div class="rhs-showcase-picker-options">' +
              data.map(function (item, index) {
                return '<button type="button" class="' + (index === activeIndex ? 'is-selected' : '') + '" data-picker-index="' + index + '" data-picker-search="' + escapeHtml(item.label.toLowerCase()) + '">' +
                  '<span>' + escapeHtml(item.label) + '</span>' +
                  '<strong>' + item.value.toLocaleString('fr-FR') + '</strong>' +
                '</button>';
              }).join('') +
              '<div class="rhs-showcase-picker-empty">Aucun resultat</div>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<span class="rhs-showcase-count">' + data.length.toLocaleString('fr-FR') + ' elements</span>' +
        '</div>' +
        '<div class="rhs-showcase-grid">' +
          '<div class="rhs-showcase-hero" style="--chart-color:' + lead.color + '">' +
            '<div class="rhs-showcase-eyebrow">Leader</div>' +
            '<strong>' + escapeHtml(lead.label) + '</strong>' +
            '<div class="rhs-showcase-total">' + lead.value.toLocaleString('fr-FR') + '<span>' + escapeHtml(unit) + '</span></div>' +
            '<div class="rhs-showcase-meter"><span style="width:' + Math.max(4, leadShare) + '%"></span></div>' +
            '<small>' + leadShare + '% du volume affiche</small>' +
          '</div>' +
          '<div class="rhs-showcase-donut" style="--a:' + lead.color + ';--b:' + second.color + ';--c:' + third.color + '">' +
            '<div class="rhs-showcase-ring"></div>' +
            '<div class="rhs-showcase-ring-center"><strong>' + total.toLocaleString('fr-FR') + '</strong><span>total</span></div>' +
          '</div>' +
          '<div class="rhs-showcase-spark">' +
            '<svg viewBox="0 0 100 58" preserveAspectRatio="none" aria-hidden="true">' +
              '<polyline points="' + sparkPoints + '" fill="none" stroke="#ef233c" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>' +
              '<polyline points="' + sparkPoints + ' 100,58 0,58" fill="rgba(239,35,60,.10)" stroke="none"></polyline>' +
            '</svg>' +
            '<span>Tendance comparative</span>' +
          '</div>' +
        '</div>' +
        '<div class="rhs-showcase-columns">' + columns + '</div>';

      bindChartInteractions(container);
      animateChartWhenVisible(container);
      const picker = container.querySelector('.rhs-showcase-picker');
      const pickerToggle = container.querySelector('.rhs-showcase-picker-toggle');

      if (picker && pickerToggle) {
        pickerToggle.addEventListener('click', function (event) {
          event.stopPropagation();
          const willOpen = !picker.classList.contains('is-open');
          closeAllPickers();
          picker.classList.toggle('is-open', willOpen);
          pickerToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
          if (willOpen) {
            const search = picker.querySelector('.rhs-showcase-picker-search');
            if (search) {
              window.setTimeout(function () { search.focus(); }, 0);
            }
          }
        });

        const search = picker.querySelector('.rhs-showcase-picker-search');
        if (search) {
          search.addEventListener('click', function (event) {
            event.stopPropagation();
          });
          search.addEventListener('input', function () {
            const term = search.value.trim().toLowerCase();
            let visible = 0;
            picker.querySelectorAll('[data-picker-index]').forEach(function (option) {
              const match = (option.getAttribute('data-picker-search') || '').includes(term);
              option.hidden = !match;
              if (match) {
                visible++;
              }
            });
            const empty = picker.querySelector('.rhs-showcase-picker-empty');
            if (empty) {
              empty.hidden = visible > 0;
            }
          });
        }

        picker.querySelectorAll('[data-picker-index]').forEach(function (option) {
          option.addEventListener('click', function (event) {
            event.stopPropagation();
            const nextIndex = Number(option.getAttribute('data-picker-index') || 0);
            closeAllPickers();
            draw(nextIndex);
          });
        });

        picker.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') {
            closeAllPickers();
            pickerToggle.focus();
          }
        });
      }
      }

      draw(0);
    }

    function renderStacked(container, data) {
      const unit = container.getAttribute('data-unit') || 'item';
      const primaryLabel = container.getAttribute('data-primary-label') || 'Principal';
      const secondaryLabel = container.getAttribute('data-secondary-label') || 'Secondaire';
      const thirdLabel = container.getAttribute('data-third-label') || '';
      const max = Math.max.apply(null, data.map(function (item) { return item.total || (item.value + item.secondary + item.third); }).concat([1]));
      const rows = data.slice(0, 10).map(function (item, index) {
        const total = item.total || (item.value + item.secondary + item.third);
        const primaryWidth = total > 0 ? (item.value / total) * 100 : 0;
        const secondaryWidth = total > 0 ? (item.secondary / total) * 100 : 0;
        const thirdWidth = total > 0 ? (item.third / total) * 100 : 0;
        const rowWidth = Math.max(6, Math.round((total / max) * 100));

        return '<button type="button" class="rhs-stacked-row" data-chart-index="' + index + '" data-chart-label="' + escapeHtml(item.label + ' : ' + total + ' ' + unit) + '">' +
          '<span class="rhs-stacked-label">' + escapeHtml(item.label) + '</span>' +
          '<span class="rhs-stacked-track" style="width:' + rowWidth + '%">' +
            '<span class="rhs-stacked-primary" style="--segment-width:' + primaryWidth + '%;--chart-delay:' + (index * 240) + 'ms"></span>' +
            '<span class="rhs-stacked-secondary" style="--segment-width:' + secondaryWidth + '%;--chart-delay:' + (index * 240 + 280) + 'ms"></span>' +
            (thirdLabel ? '<span class="rhs-stacked-third" style="--segment-width:' + thirdWidth + '%;--chart-delay:' + (index * 240 + 560) + 'ms"></span>' : '') +
          '</span>' +
          '<strong>' + total.toLocaleString('fr-FR') + '</strong>' +
        '</button>';
      }).join('');

      container.innerHTML =
        '<div class="rhs-chart-tooltip" role="status"></div>' +
        '<div class="rhs-stacked-legend">' +
          '<span><i class="rhs-stacked-primary"></i>' + escapeHtml(primaryLabel) + '</span>' +
          '<span><i class="rhs-stacked-secondary"></i>' + escapeHtml(secondaryLabel) + '</span>' +
          (thirdLabel ? '<span><i class="rhs-stacked-third"></i>' + escapeHtml(thirdLabel) + '</span>' : '') +
        '</div>' +
        '<div class="rhs-stacked-list">' + rows + '</div>';

      bindChartInteractions(container);
      animateChartWhenVisible(container);
    }

    const chartContainers = root.matches && root.matches('.rhs-chart[data-chart]')
      ? [root]
      : Array.from(root.querySelectorAll('.rhs-chart[data-chart]'));

    chartContainers.forEach(function (container) {
      if (container.dataset.chartRendered === container.getAttribute('data-chart')) {
        return;
      }

      container.dataset.chartRendered = container.getAttribute('data-chart') || '';
      let parsed = [];

      try {
        parsed = JSON.parse(container.getAttribute('data-chart') || '[]');
      } catch (error) {
        parsed = [];
      }

      const data = normalizeData(parsed);

      if (!data.length || data.every(function (item) { return item.value === 0; })) {
        container.innerHTML = '<div class="dash-empty"><div class="dash-empty-title">Aucune donnee</div><div class="dash-empty-sub">Les donnees apparaitront apres les premieres activites.</div></div>';
        return;
      }

      if ((container.getAttribute('data-chart-type') || 'bar') === 'showcase') {
        renderShowcase(container, data);
      } else if ((container.getAttribute('data-chart-type') || 'bar') === 'stacked') {
        renderStacked(container, data);
      } else if ((container.getAttribute('data-chart-type') || 'bar') === 'donut') {
        renderDonut(container, data);
      } else {
        renderBar(container, data);
      }
    });

    document.addEventListener('click', function (event) {
      if (event.target.closest('.rhs-showcase-picker')) {
        return;
      }

      document.querySelectorAll('.rhs-showcase-picker.is-open').forEach(function (picker) {
        picker.classList.remove('is-open');
        const toggle = picker.querySelector('.rhs-showcase-picker-toggle');
        if (toggle) {
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    });
  }

  window.rhsRenderDashboardCharts = renderDashboardCharts;

  ready(function () {
    if (document.body && document.body.classList.contains('admin-perf-mode')) {
      initSortableTables();
      renderDashboardCharts();
      return;
    }

    document.documentElement.classList.add('rhs-animations-enabled');

    try {
      markAnimatedElements();
      revealAnimatedElements();
      enhanceHoverTargets();
      enhanceButtons();
      enhanceFileCards();
      initSortableTables();
      renderDashboardCharts();
      animateDashboardNumbers();
    } catch (error) {
      document.documentElement.classList.remove('rhs-animations-enabled');
      document.querySelectorAll('[data-rhs-animate]').forEach(function (element) {
        element.classList.add('is-visible');
      });
    }
  });
})();
