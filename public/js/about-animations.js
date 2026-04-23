document.addEventListener("DOMContentLoaded", () => {

    // JS ONLY RUNS IF ABOUT PAGE EXISTS
    if (!document.querySelector(".about-page")) return;

    /* ================================
       1. Animate 97% badge
    ================================ */
    const ratingEl = document.querySelector(".js-rating-number");
    let ratingStarted = false;

    function animateRating() {
        if (!ratingEl || ratingStarted) return;

        const rect = ratingEl.getBoundingClientRect();
        const visible = rect.top < window.innerHeight && rect.bottom > 0;

        if (visible) {
            ratingStarted = true;
            const target = parseInt(ratingEl.dataset.target || "97", 10);
            let current = 0;
            const interval = setInterval(() => {
                current++;
                ratingEl.textContent = current + "%";
                if (current >= target) clearInterval(interval);
            }, 18);
        }
    }

    window.addEventListener("scroll", animateRating);
    animateRating();


    /* ================================
       2. Testimonials Slider (ALL testimonials)
    ================================ */
    const testimonials = [
        { initials: "JL", name: "Jannat L.", role: "Responsable RH",
          text: "La formation ‘Leadership & High-Performance Teams’ m’a permis de mieux gérer et motiver mes équipes." },

        { initials: "KB", name: "Karim B.", role: "Responsable Formation",
          text: "Les formations sur l’ingénierie de formation ont été concrètes et applicables immédiatement." },

        { initials: "SM", name: "Sofia M.", role: "Directrice Marketing",
          text: "Le conseil RH m’a permis de structurer efficacement mon équipe." },

        { initials: "AF", name: "Ahmed F.", role: "Responsable Production",
          text: "RHS Emploi nous a fourni des collaborateurs fiables et réactifs." },

        { initials: "LT", name: "Leila T.", role: "Resp. Développement Commercial",
          text: "Le coaching m’a aidée à mieux communiquer et gérer mon équipe." },

        { initials: "MA", name: "Mohamed A.", role: "Directeur Général",
          text: "Leur expertise RH a réellement amélioré nos process internes." },

        { initials: "NE", name: "Nadia E.", role: "Chargée de Recrutement",
          text: "Chaque candidat proposé correspondait exactement à nos besoins." },

        { initials: "YK", name: "Youssef K.", role: "Resp. Formation & Développement",
          text: "Les formateurs ont compris nos défis et adapté leur contenu." },

        { initials: "ID", name: "Imane D.", role: "Consultante RH",
          text: "Des solutions RH précises et concrètes adaptées à nos besoins." },

        { initials: "RS", name: "Rachid S.", role: "Chef de Division",
          text: "RHS Profil a fourni une main-d’œuvre fiable et motivée." }
    ];

    const quoteEl = document.querySelector(".js-test-quote");
    const nameEl = document.querySelector(".js-test-name");
    const roleEl = document.querySelector(".js-test-role");
    const avatarEl = document.querySelector(".js-test-avatar");
    const dotsContainer = document.querySelector(".js-test-dots");

    let currentIndex = 0;

    if (quoteEl && dotsContainer) {

        function renderTestimonial(i) {
            const t = testimonials[i];
            quoteEl.textContent = `"${t.text}"`;
            nameEl.textContent = t.name;
            roleEl.textContent = t.role;
            avatarEl.textContent = t.initials;

            [...dotsContainer.children].forEach((dot, idx) =>
                dot.classList.toggle("active", idx === i)
            );
        }

        // Build dots dynamically
        dotsContainer.innerHTML = "";
        testimonials.forEach((_, i) => {
            const dot = document.createElement("button");
            dot.onclick = () => { currentIndex = i; renderTestimonial(i); };
            dotsContainer.appendChild(dot);
        });

        // Initial display
        renderTestimonial(0);

        // Auto slide every 6.5 seconds
        setInterval(() => {
            currentIndex = (currentIndex + 1) % testimonials.length;
            renderTestimonial(currentIndex);
        }, 6500);
    }
});
