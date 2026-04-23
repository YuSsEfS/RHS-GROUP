document.addEventListener('DOMContentLoaded', () => {
    /* ============================
      HERO SLIDER
    ============================ */
    const track = document.querySelector('#hero-slider .hero-track');
    const slides = track ? Array.from(track.querySelectorAll('.slide')) : [];
    const dotsContainer = document.querySelector('.hero-dots');
    let current = 0;
    let sliderTimer;

    if (track && slides.length) {
        // Build dots only if the dotsContainer exists
        if (dotsContainer) {
            dotsContainer.innerHTML = ''; // Clear existing dots

            slides.forEach((_, index) => {
                const btn = document.createElement('button');
                if (index === 0) btn.classList.add('active');
                btn.addEventListener('click', () => goToSlide(index));
                dotsContainer.appendChild(btn);
            });
        }

        function goToSlide(index) {
            current = index;
            track.style.transform = `translateX(-${index * 100}%)`;

            [...dotsContainer.children].forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });

            restartTimer();
        }

        function nextSlide() {
            goToSlide((current + 1) % slides.length);
        }

        function restartTimer() {
            if (sliderTimer) clearInterval(sliderTimer);
            sliderTimer = setInterval(nextSlide, 8000);
        }

        restartTimer();
    }

    /* ============================
      SIGNATURE EFFECT
    ============================ */
    const signature = document.querySelector('.signature[data-signature]');
    if (signature) {
        const text = signature.getAttribute('data-signature');
        signature.textContent = '';
        let i = 0;

        function type() {
            if (i <= text.length) {
                signature.textContent = text.slice(0, i);
                i++;
                setTimeout(type, 40); // Use setTimeout for smoother typing effect
            }
        }
        type();
    }

    /* ============================
      SCROLL REVEAL + COUNTERS
    ============================ */
    const animatedBlocks = document.querySelectorAll('[data-animate]');
    const counters = document.querySelectorAll('[data-counter]');
    const counterDone = new WeakSet();

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');

                // If this block contains counters, start them
                counters.forEach(counter => {
                    if (!counterDone.has(counter) && entry.target.contains(counter)) {
                        animateCounter(counter);
                        counterDone.add(counter);
                    }
                });
            }
        });
    }, { threshold: 0.2 });

    animatedBlocks.forEach(el => observer.observe(el));

    function animateCounter(el) {
        const target = parseInt(el.dataset.target, 10) || 0;
        const suffix = el.dataset.suffix || '';
        let current = 0;
        const duration = 1500;
        const start = performance.now();

        function frame(now) {
            const progress = Math.min((now - start) / duration, 1);
            current = Math.floor(target * progress);
            el.textContent = current.toLocaleString('fr-FR') + suffix;

            if (progress < 1) requestAnimationFrame(frame);
        }

        requestAnimationFrame(frame);
    }

    /* ==========================
       NOS VALEURS HOVER EFFECT
    ========================== */
    const valeurCards = document.querySelectorAll('.valeur2-card');
    
    valeurCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-6px)'; // Apply the hover effect
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)'; // Reset the hover effect
        });
    });

    /* ==========================
       NOS VALEURS ICON ANIMATION
    ========================== */
    const valeurIcons = document.querySelectorAll('.valeur2-icon');
    
    valeurIcons.forEach(icon => {
        icon.addEventListener('mouseenter', () => {
            icon.style.backgroundColor = '#e23b31'; // Change icon background color on hover
        });

        icon.addEventListener('mouseleave', () => {
            icon.style.backgroundColor = '#fdecec'; // Reset the icon background color
        });
    });

});
