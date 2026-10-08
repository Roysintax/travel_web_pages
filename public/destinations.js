/* Combine text and region filters without navigating away from the page. */
const destinationFilters = document.getElementById('destination-filters');
const destinationQuery = document.getElementById('destination-query');
const destinationRegion = document.getElementById('destination-region');
const destinationCards = [...document.querySelectorAll('.destination-card')];
const destinationCount = document.getElementById('destination-count');
const destinationEmpty = document.getElementById('destination-empty');

function filterDestinations() {
  const query = destinationQuery.value.trim().toLowerCase();
  const region = destinationRegion.value;
  let visible = 0;

  destinationCards.forEach((card) => {
    const matchesQuery = card.dataset.search.includes(query);
    const matchesRegion = region === 'all' || card.dataset.region === region;
    card.hidden = !(matchesQuery && matchesRegion);
    if (!card.hidden) visible += 1;
  });

  destinationCount.textContent = `${visible} destination${visible === 1 ? '' : 's'} to explore`;
  destinationEmpty.hidden = visible > 0;
}

function resetDestinationFilters() {
  destinationQuery.value = '';
  destinationRegion.value = 'all';
  filterDestinations();
}

destinationQuery.addEventListener('input', filterDestinations);
destinationRegion.addEventListener('change', filterDestinations);
destinationFilters.addEventListener('submit', (event) => {
  event.preventDefault();
  filterDestinations();
});
destinationFilters.addEventListener('reset', (event) => {
  event.preventDefault();
  resetDestinationFilters();
});
document.getElementById('clear-filters').addEventListener('click', () => {
  resetDestinationFilters();
  destinationQuery.focus();
});

/* Replay the entrance when scrolling back; stop floating outside the viewport. */
const floatingCards = document.querySelectorAll('.floating-card');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let floatingObserver;

function configureFloatingAnimation() {
  floatingObserver?.disconnect();
  floatingCards.forEach((card) => card.classList.remove('is-in-view'));

  if (reducedMotion.matches || !('IntersectionObserver' in window)) return;

  floatingObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      entry.target.classList.toggle('is-in-view', entry.isIntersecting);
    });
  }, { threshold: 0.15 });

  floatingCards.forEach((card, index) => {
    card.style.setProperty('--reveal-delay', `${(index % 4) * 90}ms`);
    floatingObserver.observe(card);
  });
}

configureFloatingAnimation();
reducedMotion.addEventListener('change', configureFloatingAnimation);

const heroVideo = document.querySelector('.hero-video');
function syncHeroVideo() {
  if (reducedMotion.matches) heroVideo.pause();
  else heroVideo.play().catch(() => {});
}
syncHeroVideo();
reducedMotion.addEventListener('change', syncHeroVideo);
