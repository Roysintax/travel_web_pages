/* Packages Page Interactive Logic: Catalog, filtering, wishlist, preview modal, & calculator */
const PACKAGES_DATA = [
  {
    id: 'greece-islands',
    title: 'Greek Islands Escape',
    destination: 'Santorini & Mykonos, Greece',
    region: 'Europe',
    category: 'beach',
    badge: 'Best Seller',
    badgeClass: 'badge-orange',
    image: 'assets/greece.png',
    alt: 'White cliffside houses and blue domes of Santorini overlooking the Aegean Sea',
    days: 6,
    nights: 5,
    price: 19500000,
    originalPrice: 22500000,
    rating: 4.8,
    reviews: 142,
    desc: 'Wander whitewashed alleys, savor private sunset catamaran cruises, and unwind in cliffside boutique cave suites.',
    features: ['Flights Included', 'Caldera Suites', 'Catamaran Cruise', 'Breakfast Included'],
    itinerary: [
      { day: 'Day 1', title: 'Arrival in Athens & Ferry to Santorini', desc: 'VIP meet & greet at Athens airport, scenic catamaran ferry to Santorini, check-in to your cliffside suite with welcome wine.' },
      { day: 'Day 2', title: 'Oia Village Walking Tour & Caldera Sunset', desc: 'Guided stroll through iconic blue-domed churches, photography stops, and reserved terrace dining during sunset.' },
      { day: 'Day 3', title: 'Private Catamaran & Volcanic Hot Springs', desc: 'Full-day sailing around the caldera, swimming in geothermal springs, with Greek barbecue lunch onboard.' },
      { day: 'Day 4', title: 'Speedboat to Mykonos & Windmills Exploration', desc: 'Transfer to Mykonos, explore Little Venice, traditional windmills, and vibrant waterfront cafes.' },
      { day: 'Day 5', title: 'Delos Archaeological Island & Beach Club', desc: 'Morning historical boat tour to UNESCO-listed Delos ruins, afternoon leisure at Psarou beach.' },
      { day: 'Day 6', title: 'Souvenir Stroll & Flight Home', desc: 'Last Greek breakfast, private airport transfer, and departure flight with cherished memories.' }
    ],
    included: [
      'Round-trip international & domestic flight connections',
      '5 nights luxury 4–5 star boutique cave & beach suites',
      'Daily gourmet Aegean breakfast',
      'Private sunset catamaran excursion with BBQ lunch',
      'All airport & ferry VIP transfers'
    ],
    hotel: {
      name: 'Canaves Oia Suites & Mykonos Grand Hotel',
      stars: '5-Star Luxury',
      perks: 'Infinity pool overlooking the volcano caldera, private jacuzzi, and complimentary Champagne.'
    }
  },
  {
    id: 'maldives-paradise',
    title: 'Maldives Overwater Paradise',
    destination: 'North Malé Atoll, Maldives',
    region: 'Asia',
    category: 'beach',
    luxury: true,
    badge: 'Luxury Escape',
    badgeClass: 'badge-emerald',
    image: 'assets/maldives.png',
    alt: 'Luxury overwater bungalows and turquoise crystal lagoon in Maldives',
    days: 5,
    nights: 4,
    price: 24500000,
    originalPrice: 28500000,
    rating: 4.9,
    reviews: 188,
    desc: 'Unrivaled tropical seclusion with glass-floor water villas, manta ray reef snorkeling, and floating breakfasts.',
    features: ['Speedboat Transfer', 'Overwater Villa', 'All-Inclusive Dine', 'Coral Snorkel'],
    itinerary: [
      { day: 'Day 1', title: 'Speedboat Arrival & Overwater Villa Check-In', desc: '30-minute luxury speedboat transfer from Velana Airport directly to your private villa over the turquoise lagoon.' },
      { day: 'Day 2', title: 'Guided Coral Reef & Turtle Snorkeling Safari', desc: 'Marine biologist-guided safari discovering sea turtles, nurse sharks, and vibrant coral formations.' },
      { day: 'Day 3', title: 'Sunset Dolphin Cruise & Floating Breakfast', desc: 'Morning floating breakfast in your private plunge pool followed by an evening champagne dolphin cruise.' },
      { day: 'Day 4', title: 'Overwater Spa Treatment & Candlelit Beach Dinner', desc: '60-minute revitalizing massage with ocean-view floor glass, ending with private beach barbecue.' },
      { day: 'Day 5', title: 'Island Souvenirs & Scenic Departure', desc: 'Morning paddleboarding, farewell lunch, and transfer to Malé for your flight home.' }
    ],
    included: [
      '4 nights in an Ocean Pool Overwater Villa',
      'All-inclusive gourmet dining & premium beverages',
      'Round-trip airport speedboat transfer',
      'Guided coral reef snorkeling gear and excursion',
      'Sunset champagne dolphin safari'
    ],
    hotel: {
      name: 'Sun Siyam Olhuveli & Baros Maldives',
      stars: '5-Star Premium',
      perks: 'Direct lagoon ladder access, private sundeck, glass floor panel, 24/7 villa host.'
    }
  },
  {
    id: 'japan-discovery',
    title: 'Japan Imperial & Cultural Odyssey',
    destination: 'Kyoto, Tokyo & Nara, Japan',
    region: 'Asia',
    category: 'culture',
    badge: 'Trending',
    badgeClass: 'badge-rose',
    image: 'assets/japan.png',
    alt: 'Japanese pagoda framed by vibrant cherry blossoms and mountains',
    days: 8,
    nights: 7,
    price: 33500000,
    originalPrice: 38000000,
    rating: 4.9,
    reviews: 215,
    desc: 'Ancient shrines, bullet train travel, serene bamboo groves, traditional Ryokan onsen, and neon Tokyo city lights.',
    features: ['Shinkansen Pass', 'Onsen Ryokan', 'Kimono Experience', 'Tea Ceremony'],
    itinerary: [
      { day: 'Day 1', title: 'Konnichiwa Tokyo: Shinjuku & Shibuya', desc: 'Airport limousine transfer, check-in in Shinjuku, evening crossing at Shibuya Sky observation deck.' },
      { day: 'Day 2', title: 'Historic Asakusa & Modern Akihabara', desc: 'Senso-ji temple morning ritual, traditional Nakamise treats, and afternoon tech & anime culture.' },
      { day: 'Day 3', title: 'Bullet Train to Hakone & Mt. Fuji Onsen', desc: 'Ride the Tokaido Shinkansen to Hakone, cruise Lake Ashi, stay in a hot spring Ryokan with Kaiseki banquet.' },
      { day: 'Day 4', title: 'Scenic Train to Kyoto & Gion Geisha District', desc: 'Arrive in ancient capital Kyoto, evening lantern walking tour through historic Gion alleyways.' },
      { day: 'Day 5', title: 'Fushimi Inari Torii Gates & Arashiyama Bamboo', desc: 'Hike through 10,000 vermillion torii gates and stroll the whispering Arashiyama bamboo forest.' },
      { day: 'Day 6', title: 'Nara Deer Park & Kinkaku-ji Golden Pavilion', desc: 'Feed free-roaming sacred deer in Nara, then admire the gold leaf reflections at Kinkaku-ji.' },
      { day: 'Day 7', title: 'Authentic Tea Ceremony & Dotonbori Osaka Food Tour', desc: 'Zen matcha ceremony followed by evening culinary adventure in Osaka’s lively street food hub.' },
      { day: 'Day 8', title: 'Souvenir Hunting & Kansai Departure', desc: 'Pick up authentic matcha, ceramics, and sweets before private Kansai/Haneda airport transfer.' }
    ],
    included: [
      '7 nights accommodation including 1 night luxury onsen ryokan',
      '7-day JR Nationwide Shinkansen Rail Pass',
      'Daily breakfast & multi-course traditional Kaiseki dinner',
      'Private certified English-speaking local guides',
      'Authentic Kimono dressing & Uji tea ceremony'
    ],
    hotel: {
      name: 'The Thousand Kyoto & Hakone Kowakien Ten-yu',
      stars: '4–5 Star Luxury',
      perks: 'Private open-air forest hot spring bath, tatami living suites, gourmet seasonal dining.'
    }
  },
  {
    id: 'canada-rockies',
    title: 'Canadian Rockies Majestic Wilderness',
    destination: 'Banff & Jasper, Alberta, Canada',
    region: 'North America',
    category: 'nature',
    badge: 'Adventure',
    badgeClass: 'badge-purple',
    image: 'assets/canada.png',
    alt: 'Canoe floating on turquoise mountain lake surrounded by Canadian Rockies peaks',
    days: 7,
    nights: 6,
    price: 27500000,
    originalPrice: 30500000,
    rating: 4.7,
    reviews: 96,
    desc: 'Turquoise glacial lakes, snowcapped mountain ranges, wildlife safaris, and cozy alpine lodge firesides.',
    features: ['Park Passes', 'Lake Louise Canoe', 'Glacier Ice Walk', 'Luxury Lodges'],
    itinerary: [
      { day: 'Day 1', title: 'Calgary to Banff National Park', desc: 'Scenic mountain shuttle from Calgary to Banff township, check into your rustic-chic alpine lodge.' },
      { day: 'Day 2', title: 'Lake Louise & Moraine Lake Glacial Wonders', desc: 'Early morning canoe ride on Lake Louise and breathtaking walk along the Valley of the Ten Peaks.' },
      { day: 'Day 3', title: 'Icefields Parkway & Columbia Icefield Glacier', desc: 'Drive the world’s most scenic highway; board an all-terrain Ice Explorer onto Athabasca Glacier.' },
      { day: 'Day 4', title: 'Jasper National Park & Maligne Canyon', desc: 'Explore Jasper’s deep limestone canyon waterfalls, wildlife watching (elk, bighorn sheep, and bears).' },
      { day: 'Day 5', title: 'Maligne Lake Boat Cruise to Spirit Island', desc: 'Iconic boat excursion across crystal waters to the sacred Spirit Island viewpoint.' },
      { day: 'Day 6', title: 'Banff Gondola & Upper Hot Springs Relax', desc: 'Ride the gondola to the summit of Sulphur Mountain, followed by evening thermal mineral soaking.' },
      { day: 'Day 7', title: 'Bow River Trail & Calgary Departure', desc: 'Morning nature walk along Bow Falls, souvenir shopping, and airport transfer.' }
    ],
    included: [
      '6 nights in premium mountain lodges & Fairmont resorts',
      'All National Park entrance fees and permits',
      'Icefields Parkway Ice Explorer and Skywalk excursion',
      'Lake Louise canoe rental & Banff Gondola tickets',
      'Comfortable private AC panoramic tour coach'
    ],
    hotel: {
      name: 'Rimrock Resort Hotel Banff & Fairmont Chateau Lake Louise',
      stars: '4.5-Star Alpine',
      perks: 'Panoramic mountain vista balconies, indoor mineral pool, wood-burning fireplaces.'
    }
  },
  {
    id: 'bali-sanctuary',
    title: 'Bali Tropical Culture & Nusa Penida',
    destination: 'Ubud & Nusa Penida, Indonesia',
    region: 'Asia',
    category: 'culture',
    badge: 'Best Value',
    badgeClass: 'badge-emerald',
    image: 'assets/about/destinations.jpg',
    alt: 'Green jungle and terraced rice fields in Bali Ubud',
    days: 5,
    nights: 4,
    price: 14900000,
    originalPrice: 17900000,
    rating: 4.8,
    reviews: 167,
    desc: 'Lush emerald rice terraces, sacred water temples, artisan craft villages, and Nusa Penida cliff viewpoints.',
    features: ['Private Villa', 'Nusa Penida Boat', 'Floating Breakfast', 'Spa Massage'],
    itinerary: [
      { day: 'Day 1', title: 'Denpasar to Ubud Jungle Pool Villa', desc: 'Airport VIP escort to your secluded pool villa in Ubud, evening Balinese Kecak fire dance performance.' },
      { day: 'Day 2', title: 'Tegalalang Rice Terraces & Tirta Empul', desc: 'Sunrise stroll in lush terraces, Bali swing experience, and sacred holy water cleansing ceremony.' },
      { day: 'Day 3', title: 'Speedboat to Nusa Penida: Kelingking & Broken Beach', desc: 'Day trip to Nusa Penida, photo stop at T-Rex cliff, Angel’s Billabong, and snorkeling with manta rays.' },
      { day: 'Day 4', title: 'Ubud Cooking Class & 2-Hour Herbal Spa', desc: 'Morning organic farm market visit, cooking heritage lunch, followed by flower petal bath spa.' },
      { day: 'Day 5', title: 'Art Market Shopping & Airport Transfer', desc: 'Pick up handwoven rattan bags and silver jewelry before transfer to Ngurah Rai Airport.' }
    ],
    included: [
      '4 nights in a 5-Star Private Jungle Pool Villa in Ubud',
      'Private dedicated car & English-speaking local driver',
      'Full-day Nusa Penida private boat & tour package',
      '2-Hour Traditional Balinese spa & flower bath treatment',
      'All temple entry tickets & sarong rentals'
    ],
    hotel: {
      name: 'Komaneka at Bisma & Maya Ubud Resort',
      stars: '5-Star Sanctuary',
      perks: 'Private infinity plunge pool overlooking rainforest river valley, daily yoga sessions.'
    }
  },
  {
    id: 'swiss-alps',
    title: 'Swiss Alps Panoramic Express',
    destination: 'Zermatt, Interlaken & Lucerne, Switzerland',
    region: 'Europe',
    category: 'nature',
    luxury: true,
    badge: 'Luxury Escape',
    badgeClass: 'badge-blue',
    image: 'assets/about/planning.jpg',
    alt: 'Matterhorn peak and alpine glacier train in Switzerland',
    days: 7,
    nights: 6,
    price: 36500000,
    originalPrice: 41500000,
    rating: 4.9,
    reviews: 112,
    desc: 'Glacier Express first-class panoramic rail, majestic Matterhorn peak views, Lake Lucerne boat cruise, and Swiss fondue.',
    features: ['Swiss Travel Pass', 'Glacier Express', 'Gornergrat Train', 'Fondue Dining'],
    itinerary: [
      { day: 'Day 1', title: 'Zurich to Lucerne & Lake Steamer Cruise', desc: 'First class rail to historic Lucerne, wooden Chapel Bridge stroll, and evening sunset paddle steamer.' },
      { day: 'Day 2', title: 'Mount Pilatus Golden Round Trip', desc: 'World’s steepest cogwheel railway to Pilatus summit, aerial cableway down, chocolate tasting tour.' },
      { day: 'Day 3', title: 'Interlaken & Jungfraujoch Top of Europe', desc: 'Cogwheel train into the heart of the Eiger to Jungfraujoch ice palace, 3,454m above sea level.' },
      { day: 'Day 4', title: 'Glacier Express Ride to Car-Free Zermatt', desc: 'Famous panoramic carriage with glass roof across alpine viaducts to the foot of the Matterhorn.' },
      { day: 'Day 5', title: 'Gornergrat Alpine Cogwheel & Lake Riffelsee', desc: 'View 29 four-thousand-meter peaks, mirror reflection photos at Riffelsee, traditional fondue dinner.' },
      { day: 'Day 6', title: 'Glacier Paradise & Alpine Village Leisure', desc: 'Highest cable car station in Europe, ice tubing, shopping on Zermatt’s charming Bahnhofstrasse.' },
      { day: 'Day 7', title: 'Scenic Train to Zurich & Farewell', desc: 'First-class rail to Zurich Airport, tax-free watch shopping, and flight home.' }
    ],
    included: [
      '6 nights in premium 4–5 star Swiss Alpine chalets and hotels',
      'First Class 8-day Swiss Travel Pass for all trains and boats',
      'Seat reservations on the iconic Glacier Express',
      'Jungfraujoch Top of Europe & Gornergrat excursion passes',
      'Authentic Swiss fondue & raclette dining experience'
    ],
    hotel: {
      name: 'Omnia Zermatt & Victoria-Jungfrau Grand Hotel',
      stars: '5-Star Swiss Deluxe',
      perks: 'Matterhorn view private balconies, alpine spa with outdoor heated saline pool.'
    }
  }
];

const STORAGE_SAVED_KEY = 'travel_packages_saved_ids';

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
      <article class="package-card" data-id="${pkg.id}">
        <div class="card-media">
          <img src="${pkg.image}" alt="${pkg.alt}" loading="lazy" width="380" height="220">
          <span class="package-badge ${pkg.badgeClass}">${pkg.badge}</span>
          <button type="button" class="card-save-btn ${isSaved ? 'is-saved' : ''}" data-save-id="${pkg.id}" aria-label="${isSaved ? 'Remove from saved' : 'Save to favorites'}" title="${isSaved ? 'Remove from saved' : 'Save package'}">
            <i class="${isSaved ? 'fa-solid' : 'fa-regular'} fa-heart" aria-hidden="true"></i>
          </button>
          <span class="card-duration-tag">
            <i class="fa-regular fa-calendar-days" aria-hidden="true"></i> ${pkg.days}D / ${pkg.nights}N
          </span>
        </div>

        <div class="card-body">
          <p class="card-destination">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i> ${pkg.destination}
          </p>
          <h3 class="card-title">${pkg.title}</h3>
          <p class="card-desc">${pkg.desc}</p>

          <div class="card-features">
            ${pkg.features.map(f => `<span class="card-pill"><i class="fa-solid fa-check" aria-hidden="true"></i> ${f}</span>`).join('')}
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
              <button type="button" class="card-preview-btn" data-preview-id="${pkg.id}" aria-label="Preview ${pkg.title} itinerary" title="Quick view itinerary">
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
  document.getElementById('modal-location').innerHTML = `<i class="fa-solid fa-location-dot" aria-hidden="true"></i> ${pkg.destination}`;
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
        <strong>${item.day}: ${item.title}</strong>
        <p>${item.desc}</p>
      </li>
    `).join('');
  }

  // Populate Inclusions
  const incEl = document.getElementById('modal-included-list');
  if (incEl) {
    incEl.innerHTML = pkg.included.map(item => `
      <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600" aria-hidden="true"></i> ${item}</li>
    `).join('');
  }

  // Populate Hotel details
  const hotelEl = document.getElementById('modal-hotel-content');
  if (hotelEl) {
    hotelEl.innerHTML = `
      <div class="hotel-card">
        <img src="${pkg.image}" alt="${pkg.hotel.name}">
        <div>
          <span class="text-xs font-bold text-blue-700 block">${pkg.hotel.stars}</span>
          <h4 class="font-black text-base mt-1 text-slate-800">${pkg.hotel.name}</h4>
          <p class="text-xs text-slate-600 mt-1">${pkg.hotel.perks}</p>
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
