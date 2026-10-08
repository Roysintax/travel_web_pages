@extends('layouts.app')

@section('title', $cmsPage?->title ?? 'Travel — Around the World')
@section('meta_description', $cmsPage?->meta_description ?? '')
@section('meta_description', 'Discover featured travel packages, transparent pricing and an easy 4-step booking journey with Travel.')
@section('body_class', 'home-page')
@section('nav', 'home')

@push('styles')
  <link rel="stylesheet" href="{{ asset('home-motion.css') }}">
  <link rel="stylesheet" href="{{ asset('flight-simulator.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
@endpush

@push('scripts')
  <script src="{{ asset('home-motion.js') }}" defer></script>
  <script src="{{ asset('js/flight-simulator.js') }}" defer></script>
@endpush



@section('content')
  <section class="cinematic-journey" id="home" aria-labelledby="journey-title">
    <div class="journey-stage">
      <img class="journey-poster" src="{{ asset('assets/journey/ezgif-frame-001.jpg') }}" alt="A traveler looking through an airplane window toward the sunset" fetchpriority="high" width="1920" height="1080">
      <canvas id="journey-canvas" aria-hidden="true"></canvas>
      <div class="journey-scrim" aria-hidden="true"></div>
      <div class="journey-copy">
        <p class="journey-eyebrow" id="journey-chapter">01 / THE JOURNEY BEGINS</p>
        <h1 id="journey-title">@if(!empty($sectionHeadings['home'])){{ $sectionHeadings['home'] }}@else
Every journey<br>starts with a view.
@endif</h1>
        <p id="journey-description">Extraordinary destinations. A new perspective.</p>
        <a class="journey-cta" id="journey-cta" href="{{ url('/destinations') }}">Explore Destinations <span aria-hidden="true">↗</span></a>
      </div>
      <div class="journey-controls">
        <span class="scroll-cue"><span aria-hidden="true">↓</span> Scroll to discover</span>
        <a href="#booking" class="skip-journey">Skip journey <span aria-hidden="true">↓</span></a>
      </div>
      <div class="journey-progress" aria-hidden="true"><span></span></div>
    </div>
  </section>

  <div class="page-content relative mx-auto max-w-6xl px-5 pb-8">
    <section class="booking" id="booking" aria-label="Find your next trip">
      <div class="booking-tabs flex w-fit flex-wrap" role="group" aria-label="Travel type">
        <button class="selected" type="button" aria-pressed="true" data-type="Flights">✈ <span>Flights</span></button>
        <button type="button" aria-pressed="false" data-type="Hotels">▥ <span>Hotels</span></button>
        <button type="button" aria-pressed="false" data-type="Cars">▰ <span>Cars</span></button>
        <button type="button" aria-pressed="false" data-type="Experiences">▤ <span>Experiences</span></button>
      </div>
      <form id="search-form" class="booking-body grid gap-5 rounded-2xl p-7 md:grid-cols-3 lg:grid-cols-[1.2fr_1.2fr_1fr_1fr_.8fr_auto]">
        <label><span id="from-label">From</span><input name="from" value="New York, USA" required></label>
        <label><span id="to-label">To</span><input name="to" value="Paris, France" required></label>
        <label><span>Depart</span><input name="depart" type="date" value="2026-11-12" required></label>
        <label><span>Return</span><input name="return" type="date" value="2026-11-20" required></label>
        <label>
          <span>Travelers</span>
          <select name="travelers">
            <option>2 Adults</option>
            <option>1 Adult</option>
            <option>3 Adults</option>
            <option>4 Adults</option>
          </select>
        </label>
        <button class="primary self-center px-6 py-3" type="submit">Search</button>
        <p id="search-status" class="hidden text-sm lg:col-span-6" role="status"></p>
      </form>
    </section>

    <section aria-labelledby="benefits-title" class="benefits grid grid-cols-1 gap-6 px-5 py-8 sm:grid-cols-2 lg:grid-cols-4">
      <h2 id="benefits-title" class="sr-only">@if(!empty($sectionHeadings['section-3'])){{ $sectionHeadings['section-3'] }}@else
Why travel with us
@endif</h2>
      @foreach ([
        ['➤', 'Best Price<br>Guarantee', 'Get the best deals<br>or we match it.'],
        ['♢', 'Secure<br>Booking', 'Your data is safe<br>with us.'],
        ['♧', '24/7 Customer<br>Support', 'We’re here to help,<br>anytime.'],
        ['▣', 'Custom Travel<br>Packages', 'Tailored experiences<br>just for you.'],
      ] as [$icon, $title, $text])
        <div class="benefit">
          <div class="paper-icon" aria-hidden="true">{{ $icon }}</div>
          <div>
            <h3>{!! $title !!}</h3>
            <p>{!! $text !!}</p>
          </div>
        </div>
      @endforeach
    </section>

    <section id="packages" class="surface p-6">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="section-title">@if(!empty($sectionHeadings['packages'])){{ $sectionHeadings['packages'] }}@else
POPULAR PACKAGES
@endif</h2>
        <a href="#pricing" class="text-xs font-bold text-blue-700">View All Packages <span class="ml-2 text-lg">⊕</span></a>
      </div>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($packages as $package)
          <article class="package">
            <img loading="lazy" src="{{ asset($package->image?->file_path ?? 'assets/hero.png') }}" alt="{{ $package->image?->alt_text ?: $package->title }}">
            <div class="p-4">
              <h3>{{ $package->title }}</h3>
              <p class="details">
                ♦ {{ $package->country }}
                <span>♧ {{ $package->days }} Days / {{ $package->nights }} Nights</span>
              </p>
              <div class="package-bottom">
                <div>
                  <strong>{{ $package->formatted_price }}</strong>
                  <small>Per Person</small>
                </div>
                @if ($package->rating)
                  <span class="rating">★ <b>{{ $package->rating }}</b></span>
                @endif
                <a href="#booking" aria-label="Book {{ $package->title }}" class="circle-link">➜</a>
              </div>
            </div>
          </article>
        @empty
          <p class="text-sm text-slate-500">No packages are available right now.</p>
        @endforelse
      </div>
    </section>

    <section id="pricing" class="pricing mt-5 grid gap-0 p-6 lg:grid-cols-[155px_1fr]">
      <div class="pricing-heading">
        <h2 class="text-lg font-extrabold text-white">@if(!empty($sectionHeadings['pricing'])){{ $sectionHeadings['pricing'] }}@else
PRICING DETAILS
@endif</h2>
        <div class="mt-3 h-px w-8 bg-white/60"></div>
      </div>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-0">
        @foreach ([
          ['Economy', 'Great value for<br>smart travelers', 'Rp 7.500.000', ['Economy Class Flights', '20kg Baggage', 'Standard Hotels', 'Airport Transfers'], false],
          ['Premium', 'Comfort and<br>convenience', 'Rp 13.500.000', ['Premium Economy Flights', '25kg Baggage', '4-Star Hotels', 'Airport Transfers', 'City Tours'], true],
          ['Business', 'Luxury and<br>flexibility', 'Rp 22.500.000', ['Business Class Flights', '30kg Baggage', '5-Star Hotels', 'Airport Transfers', 'City Tours', 'Lounge Access'], false],
          ['Luxury', 'The ultimate travel<br>experience', 'Rp 37.500.000', ['First Class Flights', '35kg Baggage', 'Premium Hotels/Villas', 'Private Transfers', 'Exclusive Experiences', '24/7 Concierge'], false],
        ] as [$name, $tagline, $price, $features, $featured])
          <article @class(['plan', 'featured' => $featured])>
            @if ($featured)<span class="popular">Most Popular</span>@endif
            <h3>{{ $name }}</h3>
            <p>{!! $tagline !!}</p>
            <strong>{{ $price }}</strong>
            <small>Per Person</small>
            <ul>
              @foreach ($features as $feature)<li>{{ $feature }}</li>@endforeach
            </ul>
            <a href="#booking" @class(['primary' => $featured])>Select Plan</a>
          </article>
        @endforeach
      </div>
    </section>

    <section class="surface mt-8 px-7 pb-8" id="how-to-book">
      <h2 class="section-title relative -top-3 text-center">@if(!empty($sectionHeadings['how-to-book'])){{ $sectionHeadings['how-to-book'] }}@else
HOW TO BOOK
@endif</h2>
      <div class="mx-auto mb-6 h-px w-8 bg-blue-600"></div>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
          ['♦', '1. Choose Destination', 'Pick your dream<br>destination.'],
          ['◇', '2. Select Package', 'Choose the best<br>package for you.'],
          ['▱', '3. Make Payment', 'Secure your booking<br>with easy payment.'],
          ['➤', '4. Enjoy Your Trip', 'Pack your bags and<br>make memories!'],
        ] as [$icon, $title, $text])
          <div class="step">
            <div class="paper-icon" aria-hidden="true">{{ $icon }}</div>
            <div>
              <h3>{{ $title }}</h3>
              <p>{!! $text !!}</p>
            </div>
          </div>
        @endforeach
      </div>
    </section>

    <section aria-labelledby="about-title" id="about" class="bottom-banner mt-7 grid gap-8 p-8 text-white lg:grid-cols-[1.2fr_1fr]">
      <h2 id="about-title" class="sr-only">@if(!empty($sectionHeadings['about'])){{ $sectionHeadings['about'] }}@else
Our travelers and their experiences
@endif</h2>
      <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
        @foreach ([['✈', '150+', 'Destinations'], ['♧', '10K+', 'Happy Travelers'], ['◇', '500+', 'Travel Packages'], ['◷', '24/7', 'Support']] as [$icon, $value, $label])
          <div>
            <span>{{ $icon }}</span>
            <strong>{{ $value }}</strong>
            <small>{{ $label }}</small>
          </div>
        @endforeach
      </div>
      <blockquote class="relative pl-9 text-sm">
        <span class="quote">“</span>
        <p>The best travel experience we’ve ever had!<br>Everything was perfectly organized.</p>
        <footer class="mt-4">
          – Sarah J.<br>
          <span class="text-xs">New York, USA</span>
          <span class="ml-5 text-amber-300">★★★★★</span>
        </footer>
      </blockquote>
    </section>

    <!-- Interactive Flight Simulation & Air Traffic Radar Section -->
    <section class="flight-sim-section" id="flight-simulator-root" aria-label="Interactive Flight Simulation and Radar">
      <div class="flight-sim-card">
        <!-- Header -->
        <header class="flight-sim-header">
          <div class="flight-sim-header-left">
            <div class="flight-sim-header-icon" aria-hidden="true">
              <i class="fa-solid fa-earth-americas"></i>
            </div>
            <div class="flight-sim-title-group">
              <h2>Flight Simulation & Air Traffic Radar</h2>
              <p>Real-time Great-Circle route simulation & international air traffic corridors</p>
            </div>
          </div>
          <div class="flight-sim-header-right">
            <span class="flight-sim-status-badge">
              <span class="flight-sim-status-dot" aria-hidden="true"></span>
              <span>Simulation Mode</span>
            </span>
            <div class="flight-sim-view-toggle" role="group" aria-label="Visual Map Mode">
              <button type="button" class="flight-sim-view-btn is-active" id="flight-sim-view-globe" aria-pressed="true">
                <i class="fa-solid fa-globe" aria-hidden="true"></i> Globe 3D
              </button>
              <button type="button" class="flight-sim-view-btn" id="flight-sim-view-map" aria-pressed="false">
                <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Map 2D
              </button>
            </div>
          </div>
        </header>

        <!-- Route Selectors & Quick Presets Bar -->
        <div class="flight-sim-route-bar">
          <div class="flight-sim-selectors">
            <!-- Departure (Origin) -->
            <div class="flight-sim-airport-select-box">
              <label class="flight-sim-airport-label" for="flight-sim-origin-input">Departure Airport (From)</label>
              <div class="flight-sim-input-wrapper">
                <i class="fa-solid fa-plane-departure flight-sim-input-icon" aria-hidden="true"></i>
                <input
                  type="text"
                  id="flight-sim-origin-input"
                  class="flight-sim-input"
                  value="CGK — Soekarno-Hatta International Airport (Jakarta)"
                  placeholder="Search departure airport or IATA..."
                  autocomplete="off"
                >
              </div>
              <ul class="flight-sim-dropdown-menu" id="flight-sim-origin-dropdown" hidden></ul>
            </div>

            <!-- Swap Route Button -->
            <button type="button" class="flight-sim-swap-btn" id="flight-sim-swap-btn" aria-label="Swap departure and destination" title="Swap route">
              <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
            </button>

            <!-- Destination -->
            <div class="flight-sim-airport-select-box">
              <label class="flight-sim-airport-label" for="flight-sim-dest-input">Destination Airport (To)</label>
              <div class="flight-sim-input-wrapper">
                <i class="fa-solid fa-plane-arrival flight-sim-input-icon" aria-hidden="true"></i>
                <input
                  type="text"
                  id="flight-sim-dest-input"
                  class="flight-sim-input"
                  value="NRT — Narita International Airport (Tokyo)"
                  placeholder="Search destination airport or IATA..."
                  autocomplete="off"
                >
              </div>
              <ul class="flight-sim-dropdown-menu" id="flight-sim-dest-dropdown" hidden></ul>
            </div>
          </div>

          <!-- Quick Route Chips -->
          <div class="flight-sim-chips">
            <span class="flight-sim-chips-label"><i class="fa-solid fa-bolt" aria-hidden="true"></i> Popular Routes:</span>
            <button type="button" class="flight-sim-chip" data-from="CGK" data-to="NRT">CGK → NRT (Tokyo)</button>
            <button type="button" class="flight-sim-chip" data-from="CGK" data-to="JED">CGK → JED (Jeddah)</button>
            <button type="button" class="flight-sim-chip" data-from="SUB" data-to="SIN">SUB → SIN (Singapore)</button>
            <button type="button" class="flight-sim-chip" data-from="DPS" data-to="SYD">DPS → SYD (Sydney)</button>
            <button type="button" class="flight-sim-chip" data-from="CGK" data-to="LHR">CGK → LHR (London)</button>
          </div>
        </div>

        <!-- 3D Globe / 2D Map Canvas Stage -->
        <div class="flight-sim-stage">
          <canvas id="flight-sim-canvas" class="flight-sim-canvas" tabindex="0" role="img" aria-label="Globe interaktif dengan batas negara. Geser atau gunakan tombol panah untuk memutar; posisi tetap setelah dilepas." aria-busy="true" data-geography-url="{{ asset('assets/world-countries.geojson') }}"></canvas>


          <!-- Floating Info Card -->
          <div class="flight-sim-floating-card">
            <div class="flight-sim-flight-number">
              <i class="fa-solid fa-plane" aria-hidden="true"></i>
              <span id="flight-sim-card-flight">GA-882 (Garuda Indonesia)</span>
            </div>
            <div class="flight-sim-airline-name">Great-Circle Geodesic Trajectory</div>
            <div class="flight-sim-airports-summary">
              <span id="flight-sim-card-route">CGK → NRT</span>
              <span class="text-xs text-sky-600 font-bold"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i> ADS-B Active</span>
            </div>
          </div>

          <!-- Canvas Zoom & Compass Controls -->
          <div class="flight-sim-canvas-overlay">
            <button type="button" class="flight-sim-canvas-btn" id="flight-sim-zoom-in" aria-label="Zoom in" title="Zoom in">
              <i class="fa-solid fa-plus" aria-hidden="true"></i>
            </button>
            <button type="button" class="flight-sim-canvas-btn" id="flight-sim-zoom-out" aria-label="Zoom out" title="Zoom out">
              <i class="fa-solid fa-minus" aria-hidden="true"></i>
            </button>
            <button type="button" class="flight-sim-canvas-btn" id="flight-sim-center-cam" aria-label="Focus on aircraft" title="Center aircraft">
              <i class="fa-solid fa-crosshairs" aria-hidden="true"></i>
            </button>
          </div>

          <div class="flight-sim-canvas-hint">
            <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i> Drag / arrow keys to rotate • Scroll to zoom • Position stays where you leave it
          </div>
        </div>

        <!-- Progress Flight Corridor Bar -->
        <div class="flight-sim-progress-section">
          <div class="flight-sim-progress-track">
            <div class="flight-sim-progress-pin">
              <strong id="flight-sim-pin-origin">CGK</strong>
              <small>Depart</small>
            </div>
            <div class="flight-sim-bar-container">
              <div class="flight-sim-bar-fill" id="flight-sim-bar-fill"></div>
              <div class="flight-sim-plane-marker" id="flight-sim-plane-marker" aria-hidden="true">
                <i class="fa-solid fa-plane"></i>
              </div>
            </div>
            <div class="flight-sim-progress-pin">
              <strong id="flight-sim-pin-dest">NRT</strong>
              <small>Arrive</small>
            </div>
            <div class="flight-sim-progress-percent" id="flight-sim-percent-label">28%</div>
          </div>
        </div>

        <!-- Telemetry HUD Grid -->
        <dl class="flight-sim-hud-grid">
          <div class="flight-sim-hud-item">
            <dt><i class="fa-solid fa-arrows-up-to-line" aria-hidden="true"></i> Altitude</dt>
            <dd id="flight-sim-hud-alt">10,800<small>m</small></dd>
          </div>
          <div class="flight-sim-hud-item">
            <dt><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Ground Speed</dt>
            <dd id="flight-sim-hud-speed">870<small>km/h</small></dd>
          </div>
          <div class="flight-sim-hud-item">
            <dt><i class="fa-solid fa-route" aria-hidden="true"></i> Great-Circle Dist</dt>
            <dd id="flight-sim-hud-dist">5,812<small>km</small></dd>
          </div>
          <div class="flight-sim-hud-item">
            <dt><i class="fa-regular fa-clock" aria-hidden="true"></i> Estimated Time</dt>
            <dd id="flight-sim-hud-eta">04:32 ETA</dd>
          </div>
        </dl>

        <!-- Controls Bar -->
        <footer class="flight-sim-controls-bar">
          <div class="flight-sim-controls-left">
            <button type="button" class="flight-sim-btn flight-sim-btn-secondary" id="flight-sim-reset-btn" aria-label="Reset simulation">
              <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset
            </button>
            <button type="button" class="flight-sim-btn flight-sim-btn-primary" id="flight-sim-play-btn" aria-label="Play or pause simulation">
              <i class="fa-solid fa-pause" aria-hidden="true"></i> Pause
            </button>
            <select class="flight-sim-speed-select" id="flight-sim-speed-select" aria-label="Simulation speed">
              <option value="0.5">0.5x Speed</option>
              <option value="1" selected>1.0x Normal</option>
              <option value="2">2.0x Fast</option>
              <option value="5">5.0x Ultra</option>
              <option value="10">10x Max</option>
            </select>
          </div>

          <div class="flight-sim-controls-right">
            <label class="flight-sim-toggle-label">
              <input type="checkbox" id="flight-sim-follow-toggle">
              <span>Follow Aircraft</span>
            </label>
            <label class="flight-sim-toggle-label">
              <input type="checkbox" id="flight-sim-traffic-toggle" checked>
              <span>Show Air Traffic</span>
            </label>
          </div>
        </footer>
      </div>
    </section>
  </div>
@endsection




