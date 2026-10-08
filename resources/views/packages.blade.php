@extends('layouts.app')

@section('title', $page?->title ?? 'Travel Packages — Travel Around the World')
@section('meta_description', $page?->meta_description ?? 'Explore all-inclusive holiday packages by Travel: island escapes, cultural journeys, and mountain adventures with verified stays and 24/7 support.')
@section('body_class', 'packages-page')
@section('nav', 'packages')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/packages.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
@endpush

@push('scripts')
  <script>
    window.PACKAGES_DATA = @json($packagesJson);
  </script>
  <script src="{{ asset('js/packages.js') }}" defer></script>
@endpush

@section('content')
  <!-- Hero Section -->
  <section class="packages-hero" aria-labelledby="packages-title">
    <div class="packages-hero-art" aria-hidden="true"></div>
    <div class="mx-auto max-w-6xl px-6 packages-hero-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="{{ url('/') }}">Home</a></li>
          <li><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></li>
          <li aria-current="page">Packages</li>
        </ol>
      </nav>
      <p class="eyebrow"><i class="fa-solid fa-sparkles" aria-hidden="true"></i> Handcrafted Holiday Collections</p>
      <h1 id="packages-title">@if(!empty($sectionHeadings['section-1'])){{ $sectionHeadings['section-1'] }}@else
Tailored packages.<br><span>Unforgettable journeys.</span>
@endif</h1>
      <p class="handwriting">Dream. Pack. Discover.</p>
      <p class="hero-description">All-inclusive flights, handpicked 5-star resorts, private transfers, and curated excursions.<br class="hidden sm:block"> Choose a package below or customize your dream getaway.</p>
      
      <div class="hero-actions">
        <a href="#explore-packages" class="hero-explore">Browse Packages <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        <a href="#pricing-plans" class="hero-ghost"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Compare Plans</a>
      </div>

      <ul class="hero-stats" aria-label="Highlights at a glance">
        <li><strong><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> 100%</strong><span>Verified Stays</span></li>
        <li><strong><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Free</strong><span>Flexible Changes</span></li>
        <li><strong><i class="fa-solid fa-headset" aria-hidden="true"></i> 24/7</strong><span>Concierge Care</span></li>
        <li><strong><i class="fa-solid fa-star" aria-hidden="true"></i> 4.9 / 5</strong><span>Traveler Rating</span></li>
      </ul>
    </div>
  </section>

  <!-- Main Container -->
  <div class="mx-auto max-w-6xl px-5 packages-main">

    <!-- Filter & Search Toolbar (Sticky) -->
    <section id="explore-packages" class="surface packages-toolbar reveal" aria-labelledby="filter-heading">
      <div class="toolbar-header">
        <div>
          <p class="eyebrow text-blue-700">FIND YOUR PERFECT ESCAPE</p>
          <h2 id="filter-heading" class="text-2xl font-black">@if(!empty($sectionHeadings['explore-packages'])){{ $sectionHeadings['explore-packages'] }}@else
Featured Travel Packages
@endif</h2>
        </div>
        <div class="wishlist-counter-wrap">
          <button id="view-saved-btn" type="button" class="wishlist-pill" aria-label="View saved packages">
            <i class="fa-solid fa-heart" aria-hidden="true"></i>
            <span>Saved (<strong id="saved-count">0</strong>)</span>
          </button>
        </div>
      </div>

      <!-- Category Tabs -->
      <div class="category-tabs" role="tablist" aria-label="Package category filter">
        <button role="tab" class="cat-tab is-active" data-category="all" aria-selected="true">
          <i class="fa-solid fa-globe" aria-hidden="true"></i> All Packages
        </button>
        <button role="tab" class="cat-tab" data-category="beach" aria-selected="false">
          <i class="fa-solid fa-umbrella-beach" aria-hidden="true"></i> Beach &amp; Island
        </button>
        <button role="tab" class="cat-tab" data-category="culture" aria-selected="false">
          <i class="fa-solid fa-torii-gate" aria-hidden="true"></i> Culture &amp; Heritage
        </button>
        <button role="tab" class="cat-tab" data-category="nature" aria-selected="false">
          <i class="fa-solid fa-mountain-sun" aria-hidden="true"></i> Nature &amp; Adventure
        </button>
        <button role="tab" class="cat-tab" data-category="luxury" aria-selected="false">
          <i class="fa-solid fa-crown" aria-hidden="true"></i> Luxury Escapes
        </button>
      </div>

      <!-- Filter Controls -->
      <div class="filter-controls">
        <!-- Search input -->
        <div class="search-input-wrap">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <label for="package-search-input" class="sr-only">Search packages</label>
          <input id="package-search-input" type="search" placeholder="Search by country, city, or style…" autocomplete="off">
        </div>

        <!-- Duration Filter -->
        <div class="select-filter-wrap">
          <label for="duration-filter" class="sr-only">Filter by duration</label>
          <i class="fa-regular fa-clock" aria-hidden="true"></i>
          <select id="duration-filter">
            <option value="all">Any duration</option>
            <option value="short">Short (3–5 Days)</option>
            <option value="medium">Medium (6–7 Days)</option>
            <option value="long">Long (8+ Days)</option>
          </select>
        </div>

        <!-- Sort By -->
        <div class="select-filter-wrap">
          <label for="sort-filter" class="sr-only">Sort packages</label>
          <i class="fa-solid fa-arrow-down-wide-short" aria-hidden="true"></i>
          <select id="sort-filter">
            <option value="featured">Featured first</option>
            <option value="price-asc">Price: Low to High</option>
            <option value="price-desc">Price: High to Low</option>
            <option value="rating">Top Rated</option>
            <option value="duration">Longest Trip</option>
          </select>
        </div>

        <!-- Reset -->
        <button id="reset-filters-btn" type="button" class="reset-filter-btn" title="Reset all filters">
          <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset
        </button>
      </div>

      <!-- Results Live Status -->
      <div class="results-bar">
        <p id="results-count" class="text-xs font-bold text-slate-500" role="status" aria-live="polite">
          Showing <strong>{{ count($packages) }}</strong> packages
        </p>
        <div id="active-tag-container" class="active-tags"></div>
      </div>
    </section>

    <!-- Package Cards Grid -->
    <section class="packages-grid-section" aria-label="Package catalog">
      <div id="packages-grid" class="packages-grid">
        <!-- Populated dynamically by packages.js using window.PACKAGES_DATA -->
      </div>

      <!-- Empty State -->
      <div id="packages-empty" class="surface packages-empty-state" hidden>
        <i class="fa-solid fa-compass-drafting text-4xl text-blue-500" aria-hidden="true"></i>
        <h3 class="text-xl font-black mt-3">No matching packages found</h3>
        <p class="text-sm text-slate-500 mt-1 max-w-md">We couldn't find any holiday package matching your search or filters. Try adjusting your query or resetting the filters.</p>
        <button type="button" id="clear-all-filters-btn" class="primary px-6 py-3 mt-4">Show All Packages</button>
      </div>
    </section>

    <!-- Tiered Pricing Plans Section -->
    <section id="pricing-plans" class="pricing-section reveal" aria-labelledby="pricing-title">
      <header class="section-intro text-center">
        <p class="eyebrow text-blue-700">TRANSPARENT VALUE</p>
        <h2 id="pricing-title" class="text-3xl font-black tracking-tight">@if(!empty($sectionHeadings['pricing-plans'])){{ $sectionHeadings['pricing-plans'] }}@else
Choose Your Travel Tier
@endif</h2>
        <p class="text-slate-600 text-sm mt-2 max-w-xl mx-auto">Every tier includes our core promise: handpicked boutique stays, certified local guides, and emergency travel assistance.</p>
      </header>

      <div class="pricing-grid mt-8">
        <!-- Economy Plan -->
        <article class="plan-card">
          <div class="plan-badge-space"></div>
          <h3 class="text-lg font-black">{{ $pricingPlans->where('name', 'Economy Explorer')->first()?->name ?? 'Economy Explorer' }}</h3>
          <p class="text-xs text-slate-500 mt-1">Smart travel for backpackers &amp; value seekers</p>
          <div class="plan-price-wrap">
            <strong>{{ $pricingPlans->where('name', 'Economy Explorer')->first()?->formatted_price ?? 'Rp 7.500.000' }}</strong>
            <small>/ person base</small>
          </div>
          <ul class="plan-feature-list">
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> 3-Star verified hotels &amp; hostels</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Shared airport shuttle transfer</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Daily breakfast buffet</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Digital city audio guide</li>
            <li class="disabled"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Private chauffeur</li>
            <li class="disabled"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Flight tickets included</li>
          </ul>
          <a href="{{ url('/bookings') }}" class="plan-btn secondary-plan-btn">Choose Explorer</a>
        </article>

        <!-- Standard Plan -->
        <article class="plan-card plan-featured">
          <span class="plan-popular-pill"><i class="fa-solid fa-fire-flame-curved" aria-hidden="true"></i> MOST POPULAR</span>
          <h3 class="text-lg font-black">{{ $pricingPlans->where('name', 'Standard Comfort')->first()?->name ?? 'Standard Comfort' }}</h3>
          <p class="text-xs text-slate-500 mt-1">Balanced comfort for couples &amp; families</p>
          <div class="plan-price-wrap">
            <strong>{{ $pricingPlans->where('name', 'Standard Comfort')->first()?->formatted_price ?? 'Rp 19.500.000' }}</strong>
            <small>/ person all-in</small>
          </div>
          <ul class="plan-feature-list">
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> 4-Star boutique resort stay</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Private AC airport transfer</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Full board breakfast &amp; dinner</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> 2 Full-day guided excursions</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Fast-track attraction tickets</li>
            <li class="disabled"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Dedicated 24/7 personal butler</li>
          </ul>
          <a href="{{ url('/bookings') }}" class="plan-btn primary-plan-btn">Choose Comfort</a>
        </article>

        <!-- Luxury Plan -->
        <article class="plan-card">
          <div class="plan-badge-space"></div>
          <h3 class="text-lg font-black">{{ $pricingPlans->where('name', 'Luxury Platinum')->first()?->name ?? 'Luxury Platinum' }}</h3>
          <p class="text-xs text-slate-500 mt-1">Five-star exclusivity, wellness &amp; fine dining</p>
          <div class="plan-price-wrap">
            <strong>{{ $pricingPlans->where('name', 'Luxury Platinum')->first()?->formatted_price ?? 'Rp 33.500.000' }}</strong>
            <small>/ person luxury</small>
          </div>
          <ul class="plan-feature-list">
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> 5-Star luxury villas with private pool</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Private VIP chauffeur throughout</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> All meals &amp; Michelin-star dining</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Private yacht &amp; helicopter tour</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Dedicated 24/7 concierge &amp; butler</li>
            <li><i class="fa-solid fa-check text-blue-600" aria-hidden="true"></i> Complimentary spa &amp; wellness access</li>
          </ul>
          <a href="{{ url('/bookings') }}" class="plan-btn secondary-plan-btn">Choose Platinum</a>
        </article>
      </div>
    </section>

    <!-- Interactive Trip Calculator Widget -->
    <section class="surface calculator-section reveal" aria-labelledby="calc-title">
      <div class="calc-copy">
        <p class="eyebrow text-blue-700">INSTANT ESTIMATOR</p>
        <h2 id="calc-title" class="text-2xl font-black">@if(!empty($sectionHeadings['section-5'])){{ $sectionHeadings['section-5'] }}@else
Build &amp; Estimate Your Package
@endif</h2>
        <p class="text-sm text-slate-600 mt-2">Adjust travelers, nights, and comfort preference below to get a real-time customized package estimate.</p>
        <div class="calc-highlights">
          <div><i class="fa-solid fa-tags" aria-hidden="true"></i> Instant quote</div>
          <div><i class="fa-solid fa-calculator" aria-hidden="true"></i> No hidden fees</div>
          <div><i class="fa-solid fa-coins" aria-hidden="true"></i> Best rate guaranteed</div>
        </div>
      </div>

      <form id="calc-form" class="calc-form" novalidate>
        <div class="calc-field">
          <label for="calc-destination">Destination</label>
          <select id="calc-destination">
            @forelse ($packages as $pkg)
              <option value="{{ (int)$pkg->price_per_person }}" data-name="{{ $pkg->destination?->name ?? $pkg->title }}">
                {{ $pkg->destination?->name ?? $pkg->title }} (Base {{ $pkg->formatted_price }})
              </option>
            @empty
              <option value="19500000" data-name="Santorini, Greece">Santorini, Greece (Base Rp 19.500.000)</option>
              <option value="24500000" data-name="Maldives">Maldives Overwater (Base Rp 24.500.000)</option>
              <option value="33500000" data-name="Kyoto & Tokyo, Japan">Kyoto &amp; Tokyo, Japan (Base Rp 33.500.000)</option>
              <option value="27500000" data-name="Alberta, Canada">Alberta, Canada (Base Rp 27.500.000)</option>
              <option value="14900000" data-name="Bali & Nusa Penida">Bali &amp; Nusa Penida, ID (Base Rp 14.900.000)</option>
              <option value="36500000" data-name="Swiss Alps Panoramic">Swiss Alps Panoramic (Base Rp 36.500.000)</option>
            @endforelse
          </select>
        </div>

        <div class="calc-row">
          <div class="calc-field">
            <label for="calc-travelers">Travelers</label>
            <div class="counter-input">
              <button type="button" class="counter-btn" data-step="-1" aria-label="Decrease travelers">−</button>
              <input id="calc-travelers" type="number" value="2" min="1" max="10" readonly>
              <button type="button" class="counter-btn" data-step="1" aria-label="Increase travelers">+</button>
            </div>
          </div>

          <div class="calc-field">
            <label for="calc-nights">Trip Length</label>
            <select id="calc-nights">
              <option value="1">Standard Duration (included)</option>
              <option value="1.2">+2 Extra Nights (+20%)</option>
              <option value="1.45">+4 Extra Nights (+45%)</option>
            </select>
          </div>
        </div>

        <div class="calc-field">
          <label>Accommodation Preference</label>
          <div class="calc-tier-chips">
            <label class="calc-chip">
              <input type="radio" name="calc-tier" value="1" checked>
              <span>3-4★ Boutique</span>
            </label>
            <label class="calc-chip">
              <input type="radio" name="calc-tier" value="1.3">
              <span>5★ Resort (+30%)</span>
            </label>
            <label class="calc-chip">
              <input type="radio" name="calc-tier" value="1.7">
              <span>VIP Villa (+70%)</span>
            </label>
          </div>
        </div>

        <div class="calc-result-box">
          <div>
            <span class="text-xs text-slate-500 font-bold block uppercase tracking-wider">Estimated Total</span>
            <strong id="calc-total" class="text-3xl font-black text-blue-700">Rp 39.000.000</strong>
            <span id="calc-per-person" class="text-xs text-slate-500 block">≈ Rp 19.500.000 / person</span>
          </div>
          <a id="calc-book-btn" href="{{ url('/bookings') }}" class="primary px-6 py-3 text-sm">
            Book This Estimate <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </form>
    </section>

    <!-- Traveler Testimonials Section -->
    <section class="testimonials-section reveal" aria-labelledby="testimonials-title">
      <header class="section-intro text-center">
        <p class="eyebrow text-blue-700">REAL TRAVELER STORIES</p>
        <h2 id="testimonials-title" class="text-2xl font-black">@if(!empty($sectionHeadings['section-6'])){{ $sectionHeadings['section-6'] }}@else
Memories Created with Us
@endif</h2>
      </header>
      <div class="testimonial-grid mt-6">
        <article class="surface testimonial-card">
          <div class="rating-stars" aria-label="5 stars">★★★★★</div>
          <p class="quote-text">“The Greece Tour was meticulously organized. From the sunset catamaran to the caldera hotel, everything ran like clockwork. Truly worth every penny.”</p>
          <div class="traveler-meta">
            <span class="traveler-avatar">RS</span>
            <div>
              <strong>Raka &amp; Sarah</strong>
              <small>Greek Islands Escape · Jakarta</small>
            </div>
          </div>
        </article>
        <article class="surface testimonial-card">
          <div class="rating-stars" aria-label="5 stars">★★★★★</div>
          <p class="quote-text">“Maldives Overwater exceeded our expectations! Our concierge Nadia helped customize our anniversary dinner seamlessly. Unforgettable experience.”</p>
          <div class="traveler-meta">
            <span class="traveler-avatar">DY</span>
            <div>
              <strong>Dimas Yulianto</strong>
              <small>Maldives Paradise · Surabaya</small>
            </div>
          </div>
        </article>
        <article class="surface testimonial-card">
          <div class="rating-stars" aria-label="5 stars">★★★★★</div>
          <p class="quote-text">“Japan in autumn was magical. Tea ceremony in Kyoto, bullet train ride, and traditional Ryokan stay. Handcrafted itinerary at its absolute best.”</p>
          <div class="traveler-meta">
            <span class="traveler-avatar">AL</span>
            <div>
              <strong>Amelia Lestari</strong>
              <small>Japan Discovery · Bandung</small>
            </div>
          </div>
        </article>
      </div>
    </section>

    <!-- FAQ Accordion -->
    <section class="faq-section surface reveal" aria-labelledby="faq-title">
      <div class="faq-intro">
        <p class="eyebrow text-blue-700">HAVE QUESTIONS?</p>
        <h2 id="faq-title" class="text-2xl font-black">@if(!empty($sectionHeadings['section-7'])){{ $sectionHeadings['section-7'] }}@else
Packages FAQ
@endif</h2>
        <p class="text-xs text-slate-500 mt-1">Everything you need to know about booking, dates, and inclusions.</p>
      </div>
      <div class="faq-accordion-list">
        @forelse ($faqs as $faq)
          <details>
            <summary>{{ $faq->question }}<i class="fa-solid fa-plus" aria-hidden="true"></i></summary>
            <p>{{ $faq->answer }}</p>
          </details>
        @empty
          <details>
            <summary>What is included in the package prices?<i class="fa-solid fa-plus" aria-hidden="true"></i></summary>
            <p>Standard and Luxury packages include round-trip flights or transfers, verified 4–5 star accommodations, daily breakfast, airport VIP pick-up, and featured excursions with certified English/Indonesian-speaking guides.</p>
          </details>
          <details>
            <summary>Can I customize dates or extend our stay?<i class="fa-solid fa-plus" aria-hidden="true"></i></summary>
            <p>Yes! Every package can be extended or tailored. Use the Custom Estimator above, or contact our team via Live Chat / WhatsApp to adjust dates, room types, or add special excursions.</p>
          </details>
          <details>
            <summary>What is the cancellation and rescheduling policy?<i class="fa-solid fa-plus" aria-hidden="true"></i></summary>
            <p>We offer 100% free rescheduling up to 30 days prior to departure for all standard packages. Cancellations made between 30 to 14 days are subject to airline voucher terms with no penalty on hotel reservations.</p>
          </details>
          <details>
            <summary>Do you assist with visa applications?<i class="fa-solid fa-plus" aria-hidden="true"></i></summary>
            <p>Yes. Our dedicated visa assistance team provides document checklists, appointment bookings, and embassy submission support for Schengen (Greece), Japan, and Canadian tourist visas.</p>
          </details>
        @endforelse
      </div>
    </section>

    <!-- Bottom CTA -->
    <section class="packages-cta reveal" aria-labelledby="cta-title">
      <div>
        <p class="eyebrow text-blue-200">NEED A CUSTOM ITINERARY?</p>
        <h2 id="cta-title" class="text-3xl font-black mt-2">@if(!empty($sectionHeadings['section-8'])){{ $sectionHeadings['section-8'] }}@else
Let our AI or travel experts craft it for you.
@endif</h2>
        <p class="text-sm text-blue-100 mt-2 max-w-lg">Chat with Nadia right now on our Contact page or send us your travel dates for a bespoke proposal in under 2 hours.</p>
      </div>
      <div class="cta-actions">
        <a href="{{ url('/contact') }}" class="hero-explore">Chat Live with Us <i class="fa-solid fa-comments" aria-hidden="true"></i></a>
        <a href="{{ url('/bookings') }}" class="hero-ghost"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Start Booking</a>
      </div>
    </section>

  </div>

  <!-- Modal: Interactive Quick View Itinerary -->
  <div id="itinerary-modal" class="modal-backdrop" hidden role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-card">
      <header class="modal-header">
        <div class="modal-header-meta">
          <span id="modal-badge" class="package-badge">Island Escape</span>
          <h3 id="modal-title" class="text-2xl font-black mt-1">Greek Islands Escape</h3>
          <p id="modal-location" class="text-xs text-blue-100 mt-1"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Santorini &amp; Mykonos, Greece</p>
        </div>
        <button type="button" id="modal-close-btn" class="modal-close" aria-label="Close dialog">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </header>

      <div class="modal-tabs">
        <button type="button" class="modal-tab is-active" data-tab="itinerary">Itinerary Plan</button>
        <button type="button" class="modal-tab" data-tab="inclusions">What’s Included</button>
        <button type="button" class="modal-tab" data-tab="hotels">Resorts &amp; Stays</button>
      </div>

      <div class="modal-body">
        <!-- Tab 1: Itinerary -->
        <div id="tab-itinerary" class="tab-pane is-active">
          <ol id="modal-timeline" class="itinerary-timeline">
            <!-- Dynamically populated by JS -->
          </ol>
        </div>

        <!-- Tab 2: Inclusions -->
        <div id="tab-inclusions" class="tab-pane">
          <div class="inclusions-grid">
            <div class="inclusions-box">
              <h4 class="font-bold text-sm text-emerald-700 flex items-center gap-2">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Included in Price
              </h4>
              <ul id="modal-included-list" class="mt-3 space-y-2 text-xs text-slate-600">
                <!-- Populated by JS -->
              </ul>
            </div>
            <div class="inclusions-box">
              <h4 class="font-bold text-sm text-rose-700 flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Not Included
              </h4>
              <ul id="modal-excluded-list" class="mt-3 space-y-2 text-xs text-slate-600">
                <li>Personal expenses and optional tipping</li>
                <li>Travel insurance (available as add-on)</li>
                <li>Additional alcoholic beverages</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Tab 3: Hotels -->
        <div id="tab-hotels" class="tab-pane">
          <div id="modal-hotel-content" class="hotel-details-content">
            <!-- Populated by JS -->
          </div>
        </div>
      </div>

      <footer class="modal-footer">
        <div>
          <span class="text-xs text-slate-400 font-bold block">Starting Price</span>
          <strong id="modal-price" class="text-2xl font-black text-blue-700">Rp 19.500.000</strong>
          <small class="text-xs text-slate-500">/ person</small>
        </div>
        <div class="modal-footer-btns">
          <button type="button" id="modal-save-btn" class="modal-save-action">
            <i class="fa-regular fa-heart" aria-hidden="true"></i> Save
          </button>
          <a id="modal-book-link" href="{{ url('/bookings') }}" class="primary px-6 py-3 text-sm">
            Book This Package <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
      </footer>
    </div>
  </div>

  <!-- Toast Notification -->
  <div id="toast" class="toast-notification" hidden role="status" aria-live="polite">
    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
    <span id="toast-message">Package saved to your favorites!</span>
  </div>
@endsection

@section('footer')
  <footer class="packages-footer">
    <a href="{{ url('/') }}" class="footer-brand"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> TRAVEL</a>
    <p>© {{ date('Y') }} Travel. Around the world, with you.</p>
    <nav aria-label="Footer navigation">
      <a href="{{ url('/packages') }}" aria-current="page">Packages</a>
      <a href="{{ url('/destinations') }}">Destinations</a>
      <a href="{{ url('/about') }}">About Us</a>
      <a href="{{ url('/contact') }}">Contact</a>
    </nav>
  </footer>
@endsection


