/* Keep content readable without JavaScript; reveal motion is progressive enhancement. */
const aboutMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const aboutSections = document.querySelectorAll('.reveal');

if ('IntersectionObserver' in window) {
  const aboutObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      entry.target.classList.toggle('is-visible', entry.isIntersecting && !aboutMotion.matches);
    });
  }, { threshold: 0.12 });

  aboutSections.forEach((section) => aboutObserver.observe(section));
}

/* The decorative background plays automatically in a continuous muted loop. */
const aboutVideo = document.getElementById('about-background-video');
if (aboutVideo) {
  aboutVideo.muted = true;
  aboutVideo.loop = true;
  aboutVideo.controls = false;
  aboutVideo.play().catch(() => {
    // Keep the poster visible if the browser blocks autoplay.
  });
}
