document.addEventListener("DOMContentLoaded", () => {

    if (!document.querySelector(".about-page")) return;

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

    const testimonials = [
        {
            initials: "JL",
            name: "Jannat L.",
            role: "Responsable RH",
            text: "La formation Leadership & High-Performance Teams m'a permis de mieux gérer et motiver mes équipes.",
        },
        {
            initials: "KB",
            name: "Karim B.",
            role: "Responsable Formation",
            text: "Les formations sur l'ingénierie de formation ont été concrètes et applicables immédiatement.",
        },
        {
            initials: "SM",
            name: "Sofia M.",
            role: "Directrice Marketing",
            text: "Le conseil RH m'a permis de structurer efficacement mon équipe.",
        },
        {
            initials: "AF",
            name: "Ahmed F.",
            role: "Responsable Production",
            text: "RHS GROUP nous a fourni des collaborateurs fiables et réactifs.",
        },
        {
            initials: "LT",
            name: "Leila T.",
            role: "Resp. Développement Commercial",
            text: "Le coaching m'a aidée à mieux communiquer et gérer mon équipe.",
        },
        {
            initials: "MA",
            name: "Mohamed A.",
            role: "Directeur Général",
            text: "Leur expertise RH a réellement amélioré nos process internes.",
        },
        {
            initials: "NE",
            name: "Nadia E.",
            role: "Chargée de Recrutement",
            text: "Chaque candidat proposé correspondait exactement à nos besoins.",
        },
        {
            initials: "YK",
            name: "Youssef K.",
            role: "Resp. Formation & Développement",
            text: "Les formateurs ont compris nos défis et adapté leur contenu.",
        },
        {
            initials: "ID",
            name: "Imane D.",
            role: "Consultante RH",
            text: "Des solutions RH précises et concrètes adaptées à nos besoins.",
        },
        {
            initials: "RS",
            name: "Rachid S.",
            role: "Chef de Division",
            text: "RHS Profil a fourni une main-d'oeuvre fiable et motivée.",
        },
    ];

    const quoteEl = document.querySelector(".js-test-quote");
    const nameEl = document.querySelector(".js-test-name");
    const roleEl = document.querySelector(".js-test-role");
    const avatarEl = document.querySelector(".js-test-avatar");
    const dotsContainer = document.querySelector(".js-test-dots");
    const prevButton = document.querySelector(".js-test-prev");
    const nextButton = document.querySelector(".js-test-next");

    let currentIndex = 0;
    let testimonialTimer = null;

    if (quoteEl && dotsContainer) {
        function renderTestimonial(index) {
            const testimonial = testimonials[index];
            quoteEl.textContent = `"${testimonial.text}"`;
            nameEl.textContent = testimonial.name;
            roleEl.textContent = testimonial.role;
            avatarEl.textContent = testimonial.initials;

            Array.from(dotsContainer.children).forEach((dot, dotIndex) => {
                dot.classList.toggle("active", dotIndex === index);
            });
        }

        function goToTestimonial(index, resetTimer = true) {
            currentIndex = (index + testimonials.length) % testimonials.length;
            renderTestimonial(currentIndex);

            if (resetTimer) {
                restartTestimonials();
            }
        }

        function restartTestimonials() {
            if (testimonialTimer) clearInterval(testimonialTimer);
            testimonialTimer = setInterval(() => {
                goToTestimonial(currentIndex + 1, false);
            }, 6500);
        }

        dotsContainer.innerHTML = "";
        testimonials.forEach((_, index) => {
            const dot = document.createElement("button");
            dot.type = "button";
            dot.addEventListener("click", () => {
                goToTestimonial(index);
            });
            dotsContainer.appendChild(dot);
        });

        prevButton?.addEventListener("click", () => {
            goToTestimonial(currentIndex - 1);
        });

        nextButton?.addEventListener("click", () => {
            goToTestimonial(currentIndex + 1);
        });

        renderTestimonial(0);
        restartTestimonials();
    }
});
