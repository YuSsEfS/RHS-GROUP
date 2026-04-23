// Sticky styling on scroll
window.addEventListener("scroll", function(){
  const nav = document.getElementById("main-navbar");
  if(!nav) return;
  if(window.scrollY > 16){ nav.classList.add("scrolled"); }
  else{ nav.classList.remove("scrolled"); }
});

// Mobile drawer toggle
document.addEventListener('DOMContentLoaded', () => {
  const burger = document.getElementById('navBurger');
  const menu = document.getElementById('navMenu');
  if(!burger || !menu) return;

  burger.addEventListener('click', () => {
    const open = menu.classList.toggle('open');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Close drawer when a link is clicked
  menu.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      menu.classList.remove('open');
      burger.setAttribute('aria-expanded', 'false');
    });
  });
});
