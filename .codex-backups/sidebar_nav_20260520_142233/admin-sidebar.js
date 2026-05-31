(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.admin-sidebar');
    if (!sidebar) return;

    const groups = Array.from(sidebar.querySelectorAll('details.sidebar-group'));
    const links = Array.from(sidebar.querySelectorAll('.admin-nav > a'));
    const floatingMeta = new WeakMap();

    const OPEN_DELAY = 120;
    const CLOSE_DELAY = 240;
    const SUBMENU_WIDTH = 268;
    const GAP = 8;
    const VIEWPORT_PADDING = 12;
    const MAX_SUBMENU_HEIGHT = 420;

    let closeTimer = null;
    let openTimer = null;
    let copyTimer = null;
    let activeGroup = null;
    let activeSubmenu = null;
    let rafId = null;

    const canHover = function () {
      return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    };

    const clearDelay = function (timer) {
      if (timer) window.clearTimeout(timer);
      return null;
    };

    const cancelRaf = function () {
      if (rafId) window.cancelAnimationFrame(rafId);
      rafId = null;
    };

    const nextFrame = function (callback) {
      cancelRaf();
      rafId = window.requestAnimationFrame(function () {
        rafId = null;
        callback();
      });
    };

    const getSubmenu = function (group) {
      return group ? group.querySelector('.sidebar-submenu') : null;
    };

    const isNodeInside = function (parent, child) {
      return Boolean(parent && child && parent.contains(child));
    };

    const isSidebarOrSubmenuActive = function () {
      return (
        sidebar.matches(':hover') ||
        sidebar.matches(':focus-within') ||
        (activeSubmenu && activeSubmenu.matches(':hover')) ||
        (activeSubmenu && activeSubmenu.contains(document.activeElement))
      );
    };

    const keepSidebarOpen = function () {
      closeTimer = clearDelay(closeTimer);
      sidebar.classList.add('is-open');
    };

    const clearHoverSelection = function () {
      sidebar.querySelectorAll('.is-hover-active').forEach(function (item) {
        item.classList.remove('is-hover-active');
      });

      document.querySelectorAll('.sidebar-submenu.is-floating .is-hover-active').forEach(function (item) {
        item.classList.remove('is-hover-active');
      });
    };

    const restoreSubmenu = function (submenu) {
      if (!submenu) return;

      const meta = floatingMeta.get(submenu);

      submenu.classList.remove('is-floating');
      submenu.style.removeProperty('--rhs-submenu-left');
      submenu.style.removeProperty('--rhs-submenu-top');
      submenu.style.removeProperty('--rhs-submenu-width');

      if (meta && meta.placeholder && meta.placeholder.parentNode) {
        meta.placeholder.parentNode.insertBefore(submenu, meta.placeholder);
        meta.placeholder.remove();
      }

      floatingMeta.delete(submenu);

      if (activeSubmenu === submenu) {
        activeSubmenu = null;
      }
    };

    const closeFloatingSubmenus = function (exceptSubmenu) {
      document.querySelectorAll('.sidebar-submenu.is-floating').forEach(function (submenu) {
        if (submenu !== exceptSubmenu) restoreSubmenu(submenu);
      });

      if (!exceptSubmenu) activeSubmenu = null;
    };

    const closeGroup = function (group) {
      if (!group) return;

      const submenu = getSubmenu(group);
      restoreSubmenu(submenu);

      if (group.dataset.hoverOpen === 'true') {
        group.removeAttribute('open');
        group.removeAttribute('data-hover-open');
      }

      group.classList.remove('is-hover-active');

      if (activeGroup === group) activeGroup = null;
    };

    const closeOtherGroups = function (currentGroup) {
      groups.forEach(function (group) {
        if (group !== currentGroup) closeGroup(group);
      });
    };

    const moveSubmenuOutsideSidebar = function (submenu) {
      if (!submenu || floatingMeta.has(submenu)) return;

      const placeholder = document.createComment('rhs-sidebar-submenu');
      submenu.parentNode.insertBefore(placeholder, submenu);
      floatingMeta.set(submenu, { placeholder: placeholder });
      document.body.appendChild(submenu);
    };

    const calculateSubmenuPosition = function (group, submenu) {
      const sidebarRect = sidebar.getBoundingClientRect();
      const groupRect = group.getBoundingClientRect();
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
      const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
      const availableHeight = Math.max(180, viewportHeight - VIEWPORT_PADDING * 2);
      const submenuHeight = Math.min(submenu.scrollHeight || 220, MAX_SUBMENU_HEIGHT, availableHeight);

      const desiredLeft = sidebarRect.right + GAP;
      const maxLeft = Math.max(VIEWPORT_PADDING, viewportWidth - SUBMENU_WIDTH - VIEWPORT_PADDING);
      const left = Math.min(Math.max(desiredLeft, VIEWPORT_PADDING), maxLeft);

      const maxTop = Math.max(VIEWPORT_PADDING, viewportHeight - submenuHeight - VIEWPORT_PADDING);
      const top = Math.min(Math.max(groupRect.top, VIEWPORT_PADDING), maxTop);

      return { left: left, top: top };
    };

    const applySubmenuPosition = function (group, submenu) {
      if (!group || !submenu) return;

      const position = calculateSubmenuPosition(group, submenu);
      submenu.style.setProperty('--rhs-submenu-left', position.left + 'px');
      submenu.style.setProperty('--rhs-submenu-top', position.top + 'px');
      submenu.style.setProperty('--rhs-submenu-width', SUBMENU_WIDTH + 'px');
    };

    const positionSubmenu = function (group) {
      const submenu = getSubmenu(group);
      if (!group || !submenu) return;

      nextFrame(function () {
        if (!isSidebarOrSubmenuActive() && document.activeElement !== group) return;

        closeFloatingSubmenus(submenu);
        moveSubmenuOutsideSidebar(submenu);
        applySubmenuPosition(group, submenu);

        submenu.classList.add('is-floating');
        activeGroup = group;
        activeSubmenu = submenu;
        sidebar.classList.add('is-open', 'is-submenu-hover');
      });
    };

    const openGroup = function (group, immediate) {
      if (!group) return;

      keepSidebarOpen();
      openTimer = clearDelay(openTimer);
      clearHoverSelection();
      closeOtherGroups(group);
      sidebar.classList.remove('has-hover-active');

      group.classList.add('is-hover-active');

      if (!group.hasAttribute('open')) {
        group.setAttribute('open', 'open');
        group.dataset.hoverOpen = 'true';
      }

      const run = function () {
        if (isSidebarOrSubmenuActive()) positionSubmenu(group);
      };

      if (immediate) {
        run();
      } else {
        openTimer = window.setTimeout(run, OPEN_DELAY);
      }
    };

    const closeAll = function () {
      openTimer = clearDelay(openTimer);
      closeTimer = clearDelay(closeTimer);
      cancelRaf();

      closeFloatingSubmenus();
      clearHoverSelection();

      groups.forEach(function (group) {
        if (group.dataset.hoverOpen === 'true') {
          group.removeAttribute('open');
          group.removeAttribute('data-hover-open');
        }
      });

      sidebar.classList.remove('is-open', 'is-submenu-hover', 'has-hover-active');
      activeGroup = null;
      activeSubmenu = null;
    };

    const scheduleClose = function () {
      closeTimer = clearDelay(closeTimer);

      closeTimer = window.setTimeout(function () {
        if (isSidebarOrSubmenuActive()) {
          sidebar.classList.add('is-open');
          if (activeSubmenu) sidebar.classList.add('is-submenu-hover');
          return;
        }

        closeAll();
      }, CLOSE_DELAY);
    };

    const bindFloatingSubmenu = function (submenu, group) {
      if (!submenu || submenu.dataset.rhsBound === 'true') return;

      submenu.dataset.rhsBound = 'true';

      submenu.addEventListener('mouseenter', function () {
        keepSidebarOpen();
        sidebar.classList.add('is-submenu-hover');
        if (group) group.classList.add('is-hover-active');
      });

      submenu.addEventListener('mouseleave', scheduleClose);

      submenu.addEventListener('focusin', function () {
        keepSidebarOpen();
        sidebar.classList.add('is-submenu-hover');
        if (group) group.classList.add('is-hover-active');
      });

      submenu.addEventListener('focusout', scheduleClose);
    };

    groups.forEach(function (group) {
      const toggle = group.querySelector('.sidebar-group-toggle');
      const submenu = getSubmenu(group);

      bindFloatingSubmenu(submenu, group);

      group.addEventListener('mouseenter', function () {
        if (canHover()) openGroup(group, false);
      });

      group.addEventListener('mouseleave', scheduleClose);

      group.addEventListener('focusin', function () {
        openGroup(group, true);
      });

      if (toggle) {
        toggle.addEventListener('click', function (event) {
          event.preventDefault();

          const isCurrentOpen = activeGroup === group && sidebar.classList.contains('is-submenu-hover');

          if (isCurrentOpen) {
            closeGroup(group);
            sidebar.classList.remove('is-submenu-hover');
            if (!isSidebarOrSubmenuActive()) sidebar.classList.remove('is-open');
            return;
          }

          openGroup(group, true);
        });
      }
    });

    links.forEach(function (link) {
      const activateLink = function () {
        keepSidebarOpen();
        clearHoverSelection();
        closeFloatingSubmenus();
        closeOtherGroups(null);
        sidebar.classList.add('has-hover-active');
        sidebar.classList.remove('is-submenu-hover');
        link.classList.add('is-hover-active');
      };

      link.addEventListener('mouseenter', function () {
        if (canHover()) activateLink();
      });

      link.addEventListener('focusin', activateLink);
    });

    sidebar.addEventListener('mouseenter', function () {
      if (canHover()) keepSidebarOpen();
    });

    sidebar.addEventListener('mouseleave', function () {
      if (sidebar.contains(document.activeElement) && document.activeElement.blur) {
        document.activeElement.blur();
      }
      scheduleClose();
    });

    document.addEventListener('pointerdown', function (event) {
      const clickedInsideSidebar = isNodeInside(sidebar, event.target);
      const clickedInsideFloating = isNodeInside(activeSubmenu, event.target);

      if (!clickedInsideSidebar && !clickedInsideFloating) closeAll();
    }, true);

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeAll();
    });

    window.addEventListener('resize', function () {
      if (!activeGroup || !activeSubmenu) return;
      nextFrame(function () {
        if (activeGroup && activeSubmenu) applySubmenuPosition(activeGroup, activeSubmenu);
      });
    });

    window.addEventListener('scroll', function () {
      if (!activeGroup || !activeSubmenu) return;

      nextFrame(function () {
        if (!activeGroup || !activeSubmenu) return;

        if (isSidebarOrSubmenuActive()) {
          applySubmenuPosition(activeGroup, activeSubmenu);
        } else {
          scheduleClose();
        }
      });
    }, true);

    window.addEventListener('pagehide', closeAll);

    /* Copy whole client registration card */
    const copyCard = sidebar.querySelector('.admin-sidebar-card');

    if (copyCard) {
      const copyValue = async function () {
        const input = copyCard.querySelector('.admin-copy-input');
        const feedback = copyCard.querySelector('.admin-copy-feedback');

        if (!input || !input.value) return;

        try {
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(input.value);
          } else {
            throw new Error('Clipboard API unavailable');
          }
        } catch (error) {
          input.removeAttribute('readonly');
          input.focus();
          input.select();
          document.execCommand('copy');
          input.setAttribute('readonly', 'readonly');
          input.blur();
        }

        copyCard.classList.add('is-copied');

        if (feedback) {
          const oldText = feedback.dataset.defaultText || feedback.textContent || 'Copie';
          feedback.dataset.defaultText = oldText;
          feedback.textContent = 'Lien copie';
          feedback.classList.add('is-visible');

          copyTimer = clearDelay(copyTimer);
          copyTimer = window.setTimeout(function () {
            feedback.classList.remove('is-visible');
            feedback.textContent = feedback.dataset.defaultText || 'Copie';
            copyCard.classList.remove('is-copied');
          }, 1500);
        }
      };

      copyCard.setAttribute('role', 'button');
      copyCard.setAttribute('tabindex', '0');

      copyCard.addEventListener('click', function (event) {
        if (event.target.closest('a, button')) return;
        copyValue();
      });

      copyCard.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          copyValue();
        }
      });
    }
  });
})();
