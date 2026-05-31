document.addEventListener('DOMContentLoaded', () => {
  const panels = Array.from(document.querySelectorAll('[data-hero-panel]'));
  const images = Array.from(document.querySelectorAll('[data-hero-image]'));
  const dotsContainer = document.querySelector('.rhs-hero-dots');
  const currentSlide = document.querySelector('[data-current-slide]');
  const heroPrev = document.querySelector('.rhs-hero-prev');
  const heroNext = document.querySelector('.rhs-hero-next');
  let current = 0;
  let sliderTimer = null;
  let panelLeaveTimer = null;
  let imageLeaveTimer = null;

  function formatSlide(index) {
    return String(index + 1).padStart(2, '0');
  }

  function activateSlide(index) {
    if (!panels.length) return;
    const previous = current;
    if (previous === index) return;
    current = index;
    if (panelLeaveTimer) clearTimeout(panelLeaveTimer);
    if (imageLeaveTimer) clearTimeout(imageLeaveTimer);

    panels.forEach((panel, panelIndex) => {
      const isPrevious = panelIndex === previous;
      panel.classList.toggle('is-active', panelIndex === index);
      panel.classList.toggle('is-leaving', isPrevious && panelIndex !== index);
    });
    images.forEach((image, imageIndex) => {
      const isPrevious = imageIndex === previous;
      image.classList.toggle('is-active', imageIndex === index);
      image.classList.toggle('is-leaving', isPrevious && imageIndex !== index);
    });
    panelLeaveTimer = setTimeout(() => {
      panels.forEach((panel) => panel.classList.remove('is-leaving'));
    }, 620);
    imageLeaveTimer = setTimeout(() => {
      images.forEach((image) => image.classList.remove('is-leaving'));
    }, 820);
    if (dotsContainer) {
      Array.from(dotsContainer.children).forEach((dot, dotIndex) => {
        dot.classList.toggle('is-active', dotIndex === index);
        dot.setAttribute('aria-current', dotIndex === index ? 'true' : 'false');
      });
    }
    if (currentSlide) {
      currentSlide.textContent = formatSlide(index);
    }
  }

  function restartSlider() {
    if (sliderTimer) clearInterval(sliderTimer);
    sliderTimer = setInterval(() => {
      activateSlide((current + 1) % panels.length);
    }, 6500);
  }

  if (panels.length && dotsContainer) {
    dotsContainer.innerHTML = '';
    panels.forEach((_, index) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.setAttribute('aria-label', `Afficher la slide ${formatSlide(index)}`);
      dot.addEventListener('click', () => {
        activateSlide(index);
      });
      dotsContainer.appendChild(dot);
    });
    current = -1;
    activateSlide(0);
    restartSlider();
  }

  heroPrev?.addEventListener('click', () => {
    activateSlide((current - 1 + panels.length) % panels.length);
  });

  heroNext?.addEventListener('click', () => {
    activateSlide((current + 1) % panels.length);
  });

  const reveals = document.querySelectorAll('.rhs-reveal');
  const counters = document.querySelectorAll('[data-counter]');
  const counterDone = new WeakSet();

  const animateCounter = (counter) => {
    const target = Number.parseInt(counter.dataset.target, 10) || 0;
    const suffix = counter.dataset.suffix || '';
    const duration = 2000;
    const start = performance.now();

    const tick = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const value = Math.round(target * eased);
      counter.textContent = value.toLocaleString('fr-FR') + suffix;
      if (progress < 1) requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  };

  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      entry.target.querySelectorAll('[data-counter]').forEach((counter) => {
        if (!counterDone.has(counter)) {
          counterDone.add(counter);
          animateCounter(counter);
        }
      });
      revealObserver.unobserve(entry.target);
    });
  }, { threshold: 0.18, rootMargin: '0px 0px -50px 0px' });

  reveals.forEach((element) => revealObserver.observe(element));

  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting || counterDone.has(entry.target)) return;
      counterDone.add(entry.target);
      animateCounter(entry.target);
      counterObserver.unobserve(entry.target);
    });
  }, { threshold: 0.4 });

  counters.forEach((counter) => counterObserver.observe(counter));

  const sectorTrack = document.querySelector('.rhs-sector-track');
  const sectorCards = Array.from(document.querySelectorAll('[data-sector-card]'));
  const sectorPrev = document.querySelector('.rhs-sector-prev');
  const sectorNext = document.querySelector('.rhs-sector-next');
  const sectorDots = document.querySelector('.rhs-sector-dots');
  const sectorModal = document.getElementById('sector-modal');
  const sectorModalTitle = document.getElementById('sector-modal-title');
  const sectorModalIntro = document.getElementById('sector-modal-intro');
  const sectorModalNeeds = document.getElementById('sector-modal-needs');
  const sectorModalSolution = document.getElementById('sector-modal-solution');
  const sectorModalIcon = document.getElementById('sector-modal-icon');
  let activeSector = 0;
  let visualSector = 0;
  let sectorIsAnimating = false;
  let renderedSectorCards = sectorCards;
  let sectorTimer = null;

  function sectorStep() {
    if (!sectorTrack || !renderedSectorCards.length) return 0;
    const styles = window.getComputedStyle(sectorTrack);
    const gap = Number.parseFloat(styles.columnGap || styles.gap || '0') || 0;
    return renderedSectorCards[0].offsetWidth + gap;
  }

  function sectorTranslateFor(index) {
    const sectorWindow = document.querySelector('.rhs-sector-window');
    if (!sectorWindow || !renderedSectorCards.length) return 0;
    const windowWidth = sectorWindow.getBoundingClientRect().width;
    const cardWidth = renderedSectorCards[0].offsetWidth;
    return (windowWidth / 2) - (cardWidth / 2) - (index * sectorStep());
  }

  function moveSectorTrack(animated = true) {
    if (!sectorTrack) return;
    sectorTrack.style.transition = animated ? 'transform .9s cubic-bezier(.22, 1, .36, 1)' : 'none';
    sectorTrack.style.transform = `translateX(${sectorTranslateFor(visualSector)}px)`;
  }

  function updateSectorCarousel() {
    if (!sectorTrack || !sectorCards.length) return;
    renderedSectorCards.forEach((card) => {
      card.classList.remove('is-center');
    });
    renderedSectorCards[visualSector]?.classList.add('is-center');

    if (sectorDots) {
      Array.from(sectorDots.children).forEach((dot, index) => {
        dot.classList.toggle('is-active', index === activeSector);
        dot.setAttribute('aria-current', index === activeSector ? 'true' : 'false');
      });
    }
  }

  function restartSectorTimer() {
    if (sectorTimer) clearInterval(sectorTimer);
    if (!sectorCards.length) return;
    sectorTimer = setInterval(() => {
      goToSector(activeSector + 1);
    }, 5200);
  }

  function goToSector(index) {
    if (!sectorTrack || !sectorCards.length || sectorIsAnimating) return;

    const total = sectorCards.length;
    const target = (index + total) % total;
    let delta = target - activeSector;
    if (activeSector === 0 && target === total - 1) {
      delta = -1;
    } else if (activeSector === total - 1 && target === 0) {
      delta = 1;
    }
    if (delta === 0) {
      updateSectorCarousel();
      return;
    }

    activeSector = target;
    if (visualSector === sectorCards.length * 2 - 1 && delta === 1) {
      visualSector = sectorCards.length - 1;
      updateSectorCarousel();
      moveSectorTrack(false);
    } else if (visualSector === sectorCards.length && delta === -1) {
      visualSector = sectorCards.length * 2;
      updateSectorCarousel();
      moveSectorTrack(false);
    }
    visualSector += delta;
    sectorIsAnimating = true;
    updateSectorCarousel();
    moveSectorTrack(true);
  }

  if (sectorTrack && sectorCards.length) {
    const beforeClones = sectorCards.map((card) => {
      const clone = card.cloneNode(true);
      clone.dataset.clone = 'before';
      return clone;
    });
    const afterClones = sectorCards.map((card) => {
      const clone = card.cloneNode(true);
      clone.dataset.clone = 'after';
      return clone;
    });

    beforeClones.slice().reverse().forEach((clone) => {
      sectorTrack.insertBefore(clone, sectorTrack.firstChild);
    });
    afterClones.forEach((clone) => {
      sectorTrack.appendChild(clone);
    });
    renderedSectorCards = Array.from(sectorTrack.querySelectorAll('[data-sector-card]'));
    visualSector = sectorCards.length;

    sectorTrack.addEventListener('transitionend', (event) => {
      if (event.propertyName !== 'transform') return;
      sectorIsAnimating = false;

      if (visualSector < sectorCards.length) {
        visualSector += sectorCards.length;
        moveSectorTrack(false);
      } else if (visualSector >= sectorCards.length * 2) {
        visualSector -= sectorCards.length;
        moveSectorTrack(false);
      }

      updateSectorCarousel();
    });

    if (sectorDots) {
      sectorDots.innerHTML = '';
      sectorCards.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.setAttribute('aria-label', `Afficher le secteur ${index + 1}`);
        dot.addEventListener('click', () => {
          goToSector(index);
          restartSectorTimer();
        });
        sectorDots.appendChild(dot);
      });
    }

    sectorPrev?.addEventListener('click', () => {
      goToSector(activeSector - 1);
      restartSectorTimer();
    });
    sectorNext?.addEventListener('click', () => {
      goToSector(activeSector + 1);
      restartSectorTimer();
    });

    renderedSectorCards.forEach((card) => {
      card.addEventListener('click', () => {
        const index = Number.parseInt(card.dataset.sectorCard, 10) || 0;
        const renderedIndex = renderedSectorCards.indexOf(card);
        if (renderedIndex !== visualSector) {
          goToSector(index);
          window.setTimeout(() => openSectorModal(card), 520);
          return;
        }
        openSectorModal(card);
      });
      card.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          card.click();
        }
      });
    });

    window.addEventListener('resize', () => {
      moveSectorTrack(false);
      updateSectorCarousel();
    }, { passive: true });
    updateSectorCarousel();
    moveSectorTrack(false);
    restartSectorTimer();
  }

  function openSectorModal(card) {
    if (!sectorModal || !card) return;

    const title = card.dataset.sectorTitle || '';
    const intro = card.dataset.sectorIntro || '';
    const solution = card.dataset.sectorSolution || '';
    let needs = [];

    try {
      needs = JSON.parse(card.dataset.sectorNeeds || '[]');
    } catch (_) {
      needs = [];
    }

    if (sectorModalTitle) sectorModalTitle.textContent = title;
    if (sectorModalIntro) sectorModalIntro.textContent = intro;
    if (sectorModalSolution) sectorModalSolution.textContent = solution;
    if (sectorModalNeeds) {
      sectorModalNeeds.innerHTML = '';
      needs.forEach((need) => {
        const li = document.createElement('li');
        li.textContent = need;
        sectorModalNeeds.appendChild(li);
      });
    }
    if (sectorModalIcon) {
      const icon = card.querySelector('.rhs-sector-icon span')?.innerHTML || '';
      sectorModalIcon.innerHTML = icon;
    }

    sectorModal.classList.add('is-open');
    sectorModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('rhs-modal-open');
    sectorModal.querySelector('.rhs-sector-close')?.focus({ preventScroll: true });
  }

  function closeSectorModal() {
    if (!sectorModal) return;
    sectorModal.classList.remove('is-open');
    sectorModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('rhs-modal-open');
  }

  sectorModal?.querySelectorAll('[data-sector-close]').forEach((button) => {
    button.addEventListener('click', closeSectorModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sectorModal?.classList.contains('is-open')) {
      closeSectorModal();
    }
  });
});
