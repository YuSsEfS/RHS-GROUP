document.addEventListener('DOMContentLoaded', () => {

    const animated = document.querySelectorAll('[data-animate]');

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if(entry.isIntersecting){
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.2 });

    animated.forEach(el => observer.observe(el));

});
document.addEventListener('DOMContentLoaded', () => {

    const animated = document.querySelectorAll('[data-animate]');

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if(entry.isIntersecting){
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.2 });

    animated.forEach(el => observer.observe(el));

});
