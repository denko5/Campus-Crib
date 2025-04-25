// Add scroll effects
window.addEventListener('scroll', () => {
    const body = document.body;
    if (window.scrollY > 50) {
        body.classList.add('scrolled');
    } else {
        body.classList.remove('scrolled');
    }
});

// Smooth color transitions on scroll
let lastScrollY = 0;
window.addEventListener('scroll', () => {
    const overlay = document.querySelector('.overlay');
    const currentScrollY = window.scrollY;

    // Gradual overlay transition based on scroll direction
    if (currentScrollY > lastScrollY) {
        overlay.style.background = `rgba(0, 0, 0, ${Math.max(0.3, 0.6 - currentScrollY / 500)})`;
    }
    lastScrollY = currentScrollY;
});