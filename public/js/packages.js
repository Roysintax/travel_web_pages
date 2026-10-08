/* Packages Page Interactive Logic: Catalog, filtering, wishlist, preview modal, & calculator */
const PACKAGES_DATA = window.PACKAGES_DATA || [];

const STORAGE_SAVED_KEY = 'travel_packages_saved_ids';

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[character]));
}

// State management
let state = {
  category: 'all',
  searchQuery: '',
  duration: 'all',
  sort: 'featured',
  savedOnly: false,
  savedIds: loadSavedIds()
};

function loadSavedIds() {
  try {
    const raw = localStorage.getItem(STORAGE_SAVED_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

function persistSavedIds(ids) {
  try {
    localStorage.setItem(STORAGE_SAVED_KEY, JSON.stringify(ids));
  } catch (e) {
    console.warn('Could not save to localStorage', e);
  }
}

function showToast(message) {
  const toast = document.getElementById('toast');
  const text = document.getElementById('toast-message');
  if (!toast || !text) return;
  text.textContent = message;
  toast.hidden = false;
  clearTimeout(showToast.timeout);
  showToast.timeout = setTimeout(() => {
    toast.hidden = true;
  }, 3200);
}

/* ---------- Rendering Package Cards ---------- */
function renderPackages() {
  const container = document.getElementById('packages-grid');
  const emptyState = document.getElementById('packages-empty');
  const countEl = document.getElementById('results-count');
  const savedCountEl = document.getElementById('saved-count');
  if (!container) return;

  if (savedCountEl) {
    savedCountEl.textContent = state.savedIds.length;
  }

  // Filter items
  let filtered = PACKAGES_DATA.filter(pkg => {
    // Saved only filter
    if (state.savedOnly && !state.savedIds.includes(pkg.id)) return false;

    // Category filter
    if (state.category !== 'all') {
      if (state.category === 'luxury' && !pkg.luxury) return false;
      if (state.category !== 'luxury' && pkg.category !== state.category) return false;
    }

    // Duration filter
    if (state.duration === 'short' && pkg.days > 5) return false;
    if (state.duration === 'medium' && (pkg.days < 6 || pkg.days > 7)) return false;
    if (state.duration === 'long' && pkg.days < 8) return false;

    // Search query
    if (state.searchQuery) {
      const q = state.searchQuery.toLowerCase();
      const matchTitle = pkg.title.toLowerCase().includes(q);
      const matchDest = pkg.destination.toLowerCase().includes(q);
      const matchDesc = pkg.desc.toLowerCase().includes(q);
      const matchPills = pkg.features.some(f => f.toLowerCase().includes(q));
      if (!matchTitle && !matchDest && !matchDesc && !matchPills) return false;
    }

    return true;
  });

  // Sort items
  filtered.sort((a, b) => {
    if (state.sort === 'price-asc') return a.price - b.price;
    if (state.sort === 'price-desc') return b.price - a.price;
    if (state.sort === 'rating') return b.rating - a.rating;
    if (state.sort === 'duration') return b.days - a.days;
    return 0; // default featured
  });

  // Update count indicator
  if (countEl) {
    countEl.innerHTML = `Showing <strong>${filtered.length}</strong> of ${PACKAGES_DATA.length} packages`;
  }

  // Toggle empty state
  if (filtered.length === 0) {
    container.innerHTML = '';
    if (emptyState) emptyState.hidden = false;
    return;
  }

  if (emptyState) emptyState.hidden = true;

  // Render cards
  container.innerHTML = filtered.map(pkg => {
    const isSaved = state.savedIds.includes(pkg.id);
    return `
      <article class="package-card" data-id="${escapeHtml(pkg.id)}">
        <div class="card-media">
          <img src="${escapeHtml(pkg.image)}" alt="${escapeHtml(pkg.alt)}" loading="lazy" width="380" height="220">
          <span class="package-badge ${escapeHtml(pkg.badgeClass)}">${escapeHtml(pkg.badge)}</span>
          <button type="button" class="card-save-btn ${isSaved ? 'is-saved' : ''}" data-save-id="${escapeHtml(pkg.id)}" aria-label="${isSaved ? 'Remove from saved' : 'Save to favorites'}" title="${isSaved ? 'Remove from saved' : 'Save package'}">
            <i class="${isSaved ? 'fa-solid' : 'fa-regular'} fa-heart" aria-hidden="true"></i>
          </button>
          <span class="card-duration-tag">
            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i> ${escapeHtml(pkg.days)}D / ${escapeHtml(pkg.nights)}N
          </span>
        </div>

        <div class="card-body">
          <p class="card-destination">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i> ${escapeHtml(pkg.destination)}
          </p>
          <h3 class="card-title">${escapeHtml(pkg.title)}</h3>
          <p class="card-desc">${escapeHtml(pkg.desc)}</p>

          <div class="card-features">
            ${pkg.features.map(f => `<span class="card-pill"><i class="fa-solid fa-check" aria-hidden="true"></i> ${escapeHtml(f)}</span>`).join('')}
          </div>

          <div class="card-footer">
            <div class="card-price-block">
              <small>Starting from</small>
              <div>
                <strong>Rp ${pkg.price.toLocaleString('id-ID')}</strong>
                ${pkg.originalPrice ? `<span class="card-strikethrough">Rp ${pkg.originalPrice.toLocaleString('id-ID')}</span>` : ''}
              </div>
            </div>

            <div class="card-actions">
              <button type="button" class="card-preview-btn" data-preview-id="${escapeHtml(pkg.id)}" aria-label="Preview ${escapeHtml(pkg.title)} itinerary" title="Quick view itinerary">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
              </button>
              <a href="bookings.html?package=${encodeURIComponent(pkg.title)}" class="card-book-btn">
                Book Trip <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
          </div>
        </div>
      </article>
    `;
  }).join('');
}

/* ---------- Modal Handling ---------- */
const modal = document.getElementById('itinerary-modal');
const modalCloseBtn = document.getElementById('modal-close-btn');
const modalTabs = document.querySelectorAll('.modal-tab');
let activeModalPackage = null;

function openItineraryModal(packageId) {
  const pkg = PACKAGES_DATA.find(p => p.id === packageId);
  if (!pkg || !modal) return;
  activeModalPackage = pkg;

  // Fill modal content
  document.getElementById('modal-badge').textContent = pkg.badge;
  document.getElementById('modal-badge').className = `package-badge ${pkg.badgeClass}`;
  document.getElementById('modal-title').textContent = pkg.title;
  document.getElementById('modal-location').innerHTML = `<i class="fa-solid fa-location-dot" aria-hidden="true"></i> ${escapeHtml(pkg.destination)}`;
  document.getElementById('modal-price').textContent = `Rp ${pkg.price.toLocaleString('id-ID')}`;
  document.getElementById('modal-book-link').href = `bookings.html?package=${encodeURIComponent(pkg.title)}`;

  // Update modal save button
  const isSaved = state.savedIds.includes(pkg.id);
  const modalSaveBtn = document.getElementById('modal-save-btn');
  if (modalSaveBtn) {
    modalSaveBtn.innerHTML = `<i class="${isSaved ? 'fa-solid' : 'fa-regular'} fa-heart text-rose-500" aria-hidden="true"></i> ${isSaved ? 'Saved' : 'Save'}`;
  }

  // Populate Timeline
  const timelineEl = document.getElementById('modal-timeline');
  if (timelineEl) {
    timelineEl.innerHTML = pkg.itinerary.map(item => `
      <li>
        <strong>${escapeHtml(item.day)}: ${escapeHtml(item.title)}</strong>
        <p>${escapeHtml(item.desc)}</p>
      </li>
    `).join('');
  }

  // Populate Inclusions
  const incEl = document.getElementById('modal-included-list');
  if (incEl) {
    incEl.innerHTML = pkg.included.map(item => `
      <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600" aria-hidden="true"></i> ${escapeHtml(item)}</li>
    `).join('');
  }

  // Populate Hotel details
  const hotelEl = document.getElementById('modal-hotel-content');
  if (hotelEl) {
    hotelEl.innerHTML = `
      <div class="hotel-card">
        <img src="${escapeHtml(pkg.image)}" alt="${escapeHtml(pkg.hotel.name)}">
        <div>
          <span class="text-xs font-bold text-blue-700 block">${escapeHtml(pkg.hotel.stars)}</span>
          <h4 class="font-black text-base mt-1 text-slate-800">${escapeHtml(pkg.hotel.name)}</h4>
          <p class="text-xs text-slate-600 mt-1">${escapeHtml(pkg.hotel.perks)}</p>
        </div>
      </div>
    `;
  }

  // Reset tab to 1
  switchModalTab('itinerary');

  modal.hidden = false;
  document.body.style.overflow = 'hidden';
  modalCloseBtn?.focus();
}

function closeItineraryModal() {
  if (!modal) return;
  modal.hidden = true;
  document.body.style.overflow = '';
  activeModalPackage = null;
}

function switchModalTab(tabKey) {
  modalTabs.forEach(t => {
    t.classList.toggle('is-active', t.dataset.tab === tabKey);
  });
  document.querySelectorAll('.tab-pane').forEach(p => {
    p.classList.toggle('is-active', p.id === `tab-${tabKey}`);
  });
}

/* ---------- Custom Estimator Calculator ---------- */
function updateEstimator() {
  const destSelect = document.getElementById('calc-destination');
  const travelersInput = document.getElementById('calc-travelers');
  const nightsSelect = document.getElementById('calc-nights');
  const tierRadio = document.querySelector('input[name="calc-tier"]:checked');
  const totalEl = document.getElementById('calc-total');
  const perPersonEl = document.getElementById('calc-per-person');
  const bookBtn = document.getElementById('calc-book-btn');

  if (!destSelect || !travelersInput || !nightsSelect || !tierRadio || !totalEl) return;

  const basePrice = Number(destSelect.value) || 19500000;
  const travelers = Number(travelersInput.value) || 2;
  const nightsMultiplier = Number(nightsSelect.value) || 1;
  const tierMultiplier = Number(tierRadio.value) || 1;

  const perPerson = Math.round(basePrice * nightsMultiplier * tierMultiplier);
  const total = perPerson * travelers;

  totalEl.textContent = `Rp ${total.toLocaleString('id-ID')}`;
  if (perPersonEl) {
    perPersonEl.textContent = `≈ Rp ${perPerson.toLocaleString('id-ID')} / person`;
  }

  if (bookBtn) {
    const destName = destSelect.options[destSelect.selectedIndex].dataset.name;
    bookBtn.href = `bookings.html?destination=${encodeURIComponent(destName)}&travelers=${travelers}`;
  }
}

/* ---------- Events & Listeners Setup ---------- */
document.addEventListener('DOMContentLoaded', () => {
  renderPackages();
  updateEstimator();

  // Category tab clicks
  document.querySelectorAll('.cat-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.cat-tab').forEach(b => {
        b.classList.remove('is-active');
        b.setAttribute('aria-selected', 'false');
      });
      btn.classList.add('is-active');
      btn.setAttribute('aria-selected', 'true');
      state.category = btn.dataset.category;
      renderPackages();
    });
  });

  // Search input with debounce
  const searchInput = document.getElementById('package-search-input');
  if (searchInput) {
    let timer;
    searchInput.addEventListener('input', (e) => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        state.searchQuery = e.target.value.trim();
        renderPackages();
      }, 200);
    });
  }

  // Duration select
  const durFilter = document.getElementById('duration-filter');
  if (durFilter) {
    durFilter.addEventListener('change', (e) => {
      state.duration = e.target.value;
      renderPackages();
    });
  }

  // Sort select
  const sortFilter = document.getElementById('sort-filter');
  if (sortFilter) {
    sortFilter.addEventListener('change', (e) => {
      state.sort = e.target.value;
      renderPackages();
    });
  }

  // Reset filter button
  const resetBtn = document.getElementById('reset-filters-btn');
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      state.category = 'all';
      state.searchQuery = '';
      state.duration = 'all';
      state.sort = 'featured';
      state.savedOnly = false;

      if (searchInput) searchInput.value = '';
      if (durFilter) durFilter.value = 'all';
      if (sortFilter) sortFilter.value = 'featured';

      document.querySelectorAll('.cat-tab').forEach(b => {
        b.classList.toggle('is-active', b.dataset.category === 'all');
        b.setAttribute('aria-selected', b.dataset.category === 'all');
      });

      const savedBtn = document.getElementById('view-saved-btn');
      if (savedBtn) savedBtn.classList.remove('is-active');

      renderPackages();
      showToast('All filters have been reset.');
    });
  }

  const clearEmptyBtn = document.getElementById('clear-all-filters-btn');
  if (clearEmptyBtn && resetBtn) {
    clearEmptyBtn.addEventListener('click', () => resetBtn.click());
  }

  // Saved / Wishlist toggle view
  const savedBtn = document.getElementById('view-saved-btn');
  if (savedBtn) {
    savedBtn.addEventListener('click', () => {
      state.savedOnly = !state.savedOnly;
      savedBtn.classList.toggle('is-active', state.savedOnly);
      renderPackages();
      showToast(state.savedOnly ? 'Showing saved packages only' : 'Showing all packages');
    });
  }

  // Card click delegation: Save Heart & Preview Modal
  const gridContainer = document.getElementById('packages-grid');
  if (gridContainer) {
    gridContainer.addEventListener('click', (e) => {
      // Save button clicked
      const saveBtn = e.target.closest('[data-save-id]');
      if (saveBtn) {
        e.preventDefault();
        const id = saveBtn.dataset.saveId;
        const index = state.savedIds.indexOf(id);
        const pkg = PACKAGES_DATA.find(p => p.id === id);

        if (index > -1) {
          state.savedIds.splice(index, 1);
          showToast(`Removed “${pkg?.title}” from saved list.`);
        } else {
          state.savedIds.push(id);
          showToast(`Added “${pkg?.title}” to saved list! ❤️`);
        }
        persistSavedIds(state.savedIds);
        renderPackages();
        return;
      }

      // Preview button clicked
      const previewBtn = e.target.closest('[data-preview-id]');
      if (previewBtn) {
        e.preventDefault();
        openItineraryModal(previewBtn.dataset.previewId);
        return;
      }
    });
  }

  // Modal tab clicks
  modalTabs.forEach(tab => {
    tab.addEventListener('click', () => switchModalTab(tab.dataset.tab));
  });

  // Modal close listeners
  modalCloseBtn?.addEventListener('click', closeItineraryModal);
  modal?.addEventListener('click', (e) => {
    if (e.target === modal) closeItineraryModal();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal?.hidden) closeItineraryModal();
  });

  // Modal Save button
  const modalSaveBtn = document.getElementById('modal-save-btn');
  if (modalSaveBtn) {
    modalSaveBtn.addEventListener('click', () => {
      if (!activeModalPackage) return;
      const id = activeModalPackage.id;
      const index = state.savedIds.indexOf(id);
      if (index > -1) {
        state.savedIds.splice(index, 1);
        modalSaveBtn.innerHTML = '<i class="fa-regular fa-heart text-rose-500" aria-hidden="true"></i> Save';
        showToast('Removed from saved list.');
      } else {
        state.savedIds.push(id);
        modalSaveBtn.innerHTML = '<i class="fa-solid fa-heart text-rose-500" aria-hidden="true"></i> Saved';
        showToast('Saved to your favorites! ❤️');
      }
      persistSavedIds(state.savedIds);
      renderPackages();
    });
  }

  // Calculator counter steps (+ / -)
  document.querySelectorAll('.counter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById('calc-travelers');
      if (!input) return;
      let val = Number(input.value) + Number(btn.dataset.step);
      if (val < 1) val = 1;
      if (val > 10) val = 10;
      input.value = val;
      updateEstimator();
    });
  });

  // Calculator inputs change
  ['calc-destination', 'calc-nights'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', updateEstimator);
  });
  document.querySelectorAll('input[name="calc-tier"]').forEach(radio => {
    radio.addEventListener('change', updateEstimator);
  });

  // Reveal motion observer
  const revealItems = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    revealItems.forEach(el => revealObserver.observe(el));
  }
});
