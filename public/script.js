/* Mobile navigation: shared state for pointer and keyboard interactions. */
const menuToggle = document.querySelector('.menu-toggle');
const navigation = document.querySelector('.site-nav');
const desktopLayout = window.matchMedia('(min-width: 1024px)');

function setMenuOpen(open) {
  menuToggle.setAttribute('aria-expanded', String(open));
  menuToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
  navigation.classList.toggle('is-open', open);
  if (document.body.classList.contains('home-page')) {
    document.body.classList.toggle('menu-open', open);
  }
}

menuToggle.addEventListener('click', () => {
  setMenuOpen(menuToggle.getAttribute('aria-expanded') !== 'true');
});

navigation.addEventListener('click', (event) => {
  if (event.target.closest('a') && !desktopLayout.matches) {
    setMenuOpen(false);
    menuToggle.focus();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
    setMenuOpen(false);
    menuToggle.focus();
  }
});

document.addEventListener('click', (event) => {
  if (!event.target.closest('.site-header')) {
    setMenuOpen(false);
  }
});

desktopLayout.addEventListener('change', () => setMenuOpen(false));

/* Travel type selection updates the shared booking form. */
const travelTypes = document.querySelectorAll('[data-type]');

travelTypes.forEach((button) => {
  button.addEventListener('click', () => {
    travelTypes.forEach((item) => {
      item.classList.remove('selected');
      item.setAttribute('aria-pressed', 'false');
    });

    button.classList.add('selected');
    button.setAttribute('aria-pressed', 'true');
    const isFlight = button.dataset.type === 'Flights';
    document.getElementById('from-label').textContent = isFlight ? 'From' : 'Your location';
    document.getElementById('to-label').textContent = isFlight ? 'To' : 'Destination';
  });
});

/* Local preview only; no booking request is sent to a server. */
document.getElementById('search-form')?.addEventListener('submit', (event) => {
  event.preventDefault();

  const data = new FormData(event.target);
  const status = document.getElementById('search-status');
  const selectedType = document.querySelector('[data-type][aria-pressed="true"]');
  status.classList.remove('hidden');

  if (data.get('return') < data.get('depart')) {
    status.textContent = 'Please choose a return date after your departure.';
    return;
  }

  status.textContent = `Your ${selectedType.dataset.type.toLowerCase()} search: ` +
    `${data.get('from')} → ${data.get('to')}, ${data.get('travelers')}. ` +
    'Explore our featured packages below. This is a preview; live booking is not connected.';
});

/* Keep keyboard focus inside Home's expanded mobile navigation. */
document.addEventListener('keydown', (event) => {
  if (!document.body.classList.contains('home-page') || event.key !== 'Tab' ||
      menuToggle.getAttribute('aria-expanded') !== 'true') return;

  const links = [...navigation.querySelectorAll('a')];
  const first = links[0];
  const last = menuToggle;
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first.focus();
  }
});
