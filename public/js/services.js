document.addEventListener('DOMContentLoaded', () => {
  const animated = document.querySelectorAll('[data-animate]');
  const navLinks = Array.from(document.querySelectorAll('.services-subnav a[href^="#"]'));
  const track = document.querySelector('[data-service-track]');
  const sections = navLinks
    .map((link) => document.querySelector(link.getAttribute('href')))
    .filter(Boolean);

  const setActiveService = (index, updateHash = true) => {
    const safeIndex = Math.max(0, Math.min(index, navLinks.length - 1));
    const activeLink = navLinks[safeIndex];

    if (track) {
      track.style.transform = `translateX(-${safeIndex * 100}%)`;
    }

    navLinks.forEach((item, itemIndex) => {
      item.classList.toggle('is-active', itemIndex === safeIndex);
    });

    activeLink?.scrollIntoView({
      behavior: 'smooth',
      block: 'nearest',
      inline: 'center'
    });

    if (updateHash && activeLink) {
      history.replaceState(null, '', activeLink.getAttribute('href'));
    }
  };

  if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible', 'is-visible');
        }
      });
    }, { threshold: 0.2 });

    animated.forEach((element) => revealObserver.observe(element));
  } else {
    animated.forEach((element) => element.classList.add('visible', 'is-visible'));
  }

  navLinks.forEach((link, index) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      setActiveService(index);
    });
  });

  const initialIndex = Math.max(0, sections.findIndex((section) => `#${section.id}` === window.location.hash));
  setActiveService(initialIndex, false);
});
