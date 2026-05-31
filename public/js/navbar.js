document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('nav-toggle');
  const nav = document.getElementById('main-nav');
  const header = document.getElementById('main-header');

  if (!toggle || !nav) return;

  function closeSubmenus() {
    if (document.activeElement) {
      document.activeElement.blur();
    }
    nav.querySelectorAll('.nav-has-submenu.is-submenu-open').forEach(item => {
      item.classList.remove('is-submenu-open');
    });
    nav.querySelectorAll('.nav-submenu').forEach(menu => {
      menu.style.visibility = 'hidden';
      menu.style.opacity = '0';
      menu.style.transform = 'translateY(8px)';
      window.setTimeout(() => {
        menu.style.visibility = '';
        menu.style.opacity = '';
        menu.style.transform = '';
      }, 220);
    });
  }

  toggle.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  nav.querySelectorAll('.nav-menu a').forEach(a => {
    a.addEventListener('click', event => {
      const parentItem = a.parentElement;
      const isTopLevelSubmenuLink = window.innerWidth <= 900
        && parentItem
        && parentItem.classList.contains('nav-has-submenu')
        && parentItem.querySelector(':scope > .nav-submenu')
        && a === parentItem.querySelector(':scope > a');

      if (isTopLevelSubmenuLink) {
        event.preventDefault();
        const willOpen = !parentItem.classList.contains('is-submenu-open');

        nav.querySelectorAll('.nav-has-submenu.is-submenu-open').forEach(item => {
          if (item !== parentItem) item.classList.remove('is-submenu-open');
        });

        parentItem.classList.toggle('is-submenu-open', willOpen);
        return;
      }

      const url = new URL(a.href, window.location.href);
      const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
      const targetPath = url.pathname.replace(/\/$/, '') || '/';
      const samePage = url.origin === window.location.origin
        && targetPath === currentPath
        && url.hash;

      if (samePage) {
        const target = document.querySelector(url.hash);
        if (target) {
          event.preventDefault();
          const top = target.getBoundingClientRect().top + window.scrollY;

          window.history.pushState(null, '', url.hash);
          window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
          closeSubmenus();
        }
      }

      if (window.innerWidth <= 900) {
        nav.classList.remove('is-open');
        nav.querySelectorAll('.nav-has-submenu.is-submenu-open').forEach(item => {
          item.classList.remove('is-submenu-open');
        });
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 900) {
      nav.classList.remove('is-open');
      nav.querySelectorAll('.nav-has-submenu.is-submenu-open').forEach(item => {
        item.classList.remove('is-submenu-open');
      });
      toggle.setAttribute('aria-expanded', 'false');
    }
  });

  if (window.location.hash) {
    const target = document.querySelector(window.location.hash);
    if (target) {
      window.setTimeout(() => {
        const top = target.getBoundingClientRect().top + window.scrollY;
        window.scrollTo({ top: Math.max(top, 0), behavior: 'auto' });
      }, 0);
    }
  }

  if (header) {
    let lastScrollY = window.scrollY;
    let ticking = false;

    function updateHeaderVisibility() {
      const currentScrollY = window.scrollY;
      const scrollingDown = currentScrollY > lastScrollY;
      const isNearTop = currentScrollY < 24;

      header.classList.toggle('is-hidden', scrollingDown && !isNearTop && currentScrollY > 140);
      if (nav.classList.contains('is-open')) {
        header.classList.remove('is-hidden');
      }
      header.classList.toggle('is-compact', currentScrollY > 24);

      lastScrollY = Math.max(currentScrollY, 0);
      ticking = false;
    }

    window.addEventListener('scroll', () => {
      if (!ticking) {
        window.requestAnimationFrame(updateHeaderVisibility);
        ticking = true;
      }
    }, { passive: true });
  }
});
