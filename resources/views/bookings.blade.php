@extends('layouts.app')

@section('title', $page?->title ?? 'Bookings — Travel Around the World')
@section('meta_description', $page?->meta_description ?? 'Plan your next Travel getaway, choose a destination, and review your trip estimate.')
@section('body_class', 'bookings-page')
@section('nav', 'bookings')

@push('styles')
  <link rel="stylesheet" href="{{ asset('bookings.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
@endpush

@section('header')
  <header class="site-header mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-6">
    <a href="{{ url('/') }}" class="logo flex items-center gap-2">
      <i class="fa-solid fa-paper-plane brand-plane" aria-hidden="true"></i>
      <span class="text-3xl tracking-wider">
        TRAVEL
        <small class="block text-[9px] font-extrabold tracking-normal">
          AROUND THE WORLD
        </small>
      </span>
    </a>
    <nav id="primary-navigation" aria-label="Main navigation" class="site-nav">
      <ul class="nav-list">
        <li>
          <a href="{{ url('/') }}">
            Home
          </a>
        </li>
        <li>
          <a href="{{ url('/packages') }}">
            Packages
          </a>
        </li>
        <li>
          <a href="{{ url('/destinations') }}">
            Destinations
          </a>
        </li>
        <li>
          <a class="active" aria-current="page" href="{{ url('/bookings') }}">
            Bookings
          </a>
        </li>
        <li>
          <a href="{{ url('/about') }}">
            About Us
          </a>
        </li>
        <li>
          <a href="{{ url('/contact') }}">
            Contact
          </a>
        </li>
      </ul>
    </nav>
    <a href="{{ url('/bookings') }}" class="header-booking rounded-full border border-white px-5 py-2 text-sm">
      Book a Trip <i class="fa-solid fa-user" aria-hidden="true"></i>
    </a>
    <button class="menu-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" aria-label="Open navigation">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </header>
@endsection

@section('content')
  <section class="booking-hero" aria-labelledby="booking-title">
    <div class="mx-auto max-w-6xl px-6 booking-hero-inner">
      <div>
        <p class="booking-eyebrow">A LITTLE PLANNING. A WORLD OF POSSIBILITY.</p>
        <h1 id="booking-title">@if(!empty($sectionHeadings['section-1'])){{ $sectionHeadings['section-1'] }}@else
Your next chapter<br>starts here.
@endif</h1>
        <p>Choose your escape. Make it yours.<br>We’ll bring the details together.</p>
      </div>
      <div class="hero-ticket" aria-hidden="true">
        <i class="fa-solid fa-plane-departure"></i>
        <span>YOUR NEXT ADVENTURE</span>
        <strong>READY FOR TAKEOFF</strong>
        <div class="ticket-route">YOU <span>······ ✈ ······</span> WORLD</div>
      </div>
    </div>
  </section>

  <div class="booking-container mx-auto max-w-6xl px-6">
    <ol class="booking-steps" aria-label="Booking preview steps">
      <li class="current" aria-current="step"><span>1</span> Plan your trip</li>
      <li><span>2</span> Review your details</li>
      <li><span>3</span> Ready to explore</li>
    </ol>
    <div class="booking-layout">
      <form id="trip-form" class="trip-form" method="POST" action="{{ route('bookings.store') }}">
        @csrf
        <section class="booking-panel" aria-labelledby="choose-title">
          <header class="panel-heading">
            <span class="panel-icon"><i class="fa-solid fa-earth-americas" aria-hidden="true"></i></span>
            <div>
              <h2 id="choose-title">@if(!empty($sectionHeadings['section-2'])){{ $sectionHeadings['section-2'] }}@else
Where are we heading?
@endif</h2>
              <p>Your dream destination is closer than you think.</p>
            </div>
          </header>
          <fieldset class="destination-options">
            <legend class="sr-only">Choose your travel package</legend>
            @php
              $displayPackages = [
                'greece' => ['name' => 'Greek Islands Escape', 'country' => 'Greece', 'price' => '19.500.000', 'img' => 'assets/greece.png'],
                'maldives' => ['name' => 'Maldives Paradise', 'country' => 'Maldives', 'price' => '24.500.000', 'img' => 'assets/maldives.png'],
                'canada' => ['name' => 'Canadian Rockies', 'country' => 'Canada', 'price' => '27.500.000', 'img' => 'assets/canada.png'],
                'japan' => ['name' => 'Japan Discovery', 'country' => 'Japan', 'price' => '33.500.000', 'img' => 'assets/japan.png'],
              ];
              if (isset($packages) && $packages->isNotEmpty()) {
                foreach ($packages as $p) {
                  $k = $p->booking_key ?: $p->slug;
                  $c = $p->destination?->name ? (explode(',', $p->destination->name)[1] ?? $p->destination->name) : 'International';
                  $displayPackages[$k] = [
                    'name' => $p->title,
                    'country' => trim($c) ?: ($displayPackages[$k]['country'] ?? 'International'),
                    'price' => number_format((float) $p->price_per_person, 0, ',', '.'),
                    'img' => $p->image?->file_path ?: "assets/{$k}.png",
                  ];
                }
              }
            @endphp
            @foreach ($displayPackages as $key => $pkg)
              <label class="trip-option">
                <input type="radio" name="package" value="{{ $key }}" @checked($loop->first)>
                <img src="{{ asset($pkg['img']) }}" alt="" width="189" height="130">
                <span class="option-body">
                  <strong>{{ $pkg['name'] }}</strong>
                  <small>{{ $pkg['country'] }}</small>
                  <span>From Rp {{ $pkg['price'] }} / person</span>
                </span>
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
              </label>
            @endforeach
          </fieldset>
        </section>

        <section class="booking-panel" aria-labelledby="details-title">
          <header class="panel-heading">
            <span class="panel-icon"><i class="fa-regular fa-calendar-days" aria-hidden="true"></i></span>
            <div>
              <h2 id="details-title">@if(!empty($sectionHeadings['section-3'])){{ $sectionHeadings['section-3'] }}@else
Make the journey yours
@endif</h2>
              <p>A few details to shape your getaway.</p>
            </div>
          </header>
          <div class="trip-fields">
            <label>Departure date<input id="trip-date" name="departure" type="date" required></label>
            <label>Travelers
              <select id="trip-travelers" name="travelers">
                <option value="1">1 adult</option>
                <option value="2" selected>2 adults</option>
                <option value="3">3 adults</option>
                <option value="4">4 adults</option>
                <option value="5">5 adults</option>
                <option value="6">6 adults</option>
              </select>
            </label>
            <div class="full-field airport-dropdown-field">
              <label for="trip-origin-input">Departing from</label>
              <input type="hidden" id="trip-origin" name="origin" value="" required>
              <input type="hidden" id="trip-origin-label" name="origin_label" value="">

              <div class="airport-combobox" id="airport-combobox" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="airport-results">
                <div class="airport-input-wrapper">
                  <i class="fa-solid fa-plane-departure airport-input-icon" aria-hidden="true"></i>
                  <input
                    type="text"
                    id="trip-origin-input"
                    class="airport-search-input"
                    placeholder="Search airport, city, or IATA code (e.g. CGK, Jakarta)..."
                    autocomplete="off"
                    aria-autocomplete="list"
                    aria-controls="airport-results"
                    spellcheck="false"
                    required
                  >
                  <button type="button" class="airport-clear-btn" id="airport-clear-btn" aria-label="Clear selection" title="Clear selection" hidden>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                  </button>
                  <button type="button" class="airport-toggle-btn" id="airport-toggle-btn" aria-label="Toggle airports list" tabindex="-1">
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                  </button>
                </div>

                <div class="airport-dropdown-menu" id="airport-dropdown-menu" hidden>
                  <div class="airport-dropdown-header">
                    <span id="airport-dropdown-title">Popular Departure Airports</span>
                    <span class="airport-hint"><i class="fa-solid fa-keyboard" aria-hidden="true"></i> Type to search</span>
                  </div>

                  <div class="airport-dropdown-state airport-loading" id="airport-loading" hidden>
                    <i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i>
                    <span>Searching airports...</span>
                  </div>

                  <div class="airport-dropdown-state airport-error" id="airport-error" hidden>
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span>Failed to load airport list.</span>
                    <button type="button" class="airport-retry-btn" id="airport-retry-btn">Try again</button>
                  </div>

                  <div class="airport-dropdown-state airport-empty" id="airport-empty" hidden>
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>No airports found matching your search.</span>
                  </div>

                  <ul class="airport-results-list" id="airport-results" role="listbox" aria-label="Departure Airports"></ul>
                </div>
              </div>
            </div>
          </div>
          <p class="field-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Trip duration follows your selected package. Prices are indicative.</p>
        </section>

        <section class="booking-panel" aria-labelledby="traveler-title">
          <header class="panel-heading">
            <span class="panel-icon"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
            <div>
              <h2 id="traveler-title">@if(!empty($sectionHeadings['section-4'])){{ $sectionHeadings['section-4'] }}@else
Who’s leading the adventure?
@endif</h2>
              <p>Details for your local trip preview.</p>
            </div>
          </header>
          <div class="trip-fields">
            <label>Full name<input id="trip-name" name="name" autocomplete="name" placeholder="Your full name" maxlength="100" required></label>
            <label>Email address<input id="trip-email" name="email" type="email" autocomplete="email" placeholder="you@example.com" maxlength="254" required></label>
            <label class="full-field">Anything you’d like to add? <span class="optional">(optional)</span><textarea id="trip-notes" name="notes" rows="3" maxlength="500" placeholder="A special occasion, preferred pace, or accessibility needs…"></textarea></label>
          </div>
        </section>

        <div class="preview-notice">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <p>This is a booking preview. No reservation, payment, or email is sent. Your draft stays in this browser tab while you move between steps. You can clear it at the final step.</p>
        </div>
        <p id="draft-error" class="flow-error" role="alert" hidden>Your browser blocked draft storage. Enable session storage to continue between booking pages.</p>
        <button type="submit" class="review-button">Review My Trip <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
        <noscript><p>This interactive preview needs JavaScript. You can still explore our <a href="{{ url('/destinations') }}">destinations</a>.</p></noscript>
      </form>

      <aside class="trip-summary" aria-labelledby="summary-title">
        <div class="summary-photo">
          <img id="summary-image" src="{{ asset('assets/greece.png') }}" alt="Greek Islands Escape" width="390" height="260">
          <span><i class="fa-solid fa-suitcase-rolling" aria-hidden="true"></i> YOUR GETAWAY</span>
        </div>
        <div class="summary-content">
          <p class="booking-eyebrow">GOOD TIMES AHEAD</p>
          <h2 id="summary-title">Greek Islands Escape</h2>
          <p id="summary-duration" class="summary-duration">6 days / 5 nights · Greece</p>
          <dl class="summary-facts">
            <div><dt><i class="fa-regular fa-calendar" aria-hidden="true"></i> Departure</dt><dd id="summary-date">Choose a date</dd></div>
            <div><dt><i class="fa-solid fa-users" aria-hidden="true"></i> Travelers</dt><dd id="summary-travelers">2 adults</dd></div>
            <div><dt><i class="fa-solid fa-plane-departure" aria-hidden="true"></i> From</dt><dd id="summary-origin">Your departure city</dd></div>
          </dl>
          <div class="price-line">
            <span>Package / person</span>
            <strong id="summary-unit">Rp 19.500.000</strong>
          </div>
          <div class="price-line total-line">
            <span>Estimated total<small>For all travelers</small></span>
            <strong id="summary-total" aria-live="polite">Rp 39.000.000</strong>
          </div>
          <p class="summary-note">IDR · Illustrative package estimate. Availability and final pricing are not confirmed.</p>
          <ul class="summary-inclusions">
            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Selected accommodation</li>
            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Curated travel experiences</li>
            <li><i class="fa-solid fa-check" aria-hidden="true"></i> A little adventure, a lot of memories</li>
          </ul>
          <a href="{{ url('/destinations') }}" class="summary-link">Still exploring? View destinations <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
      </aside>
    </div>

    <section class="booking-reassurance" aria-label="Travel planning benefits">
      <div>
        <i class="fa-solid fa-compass" aria-hidden="true"></i>
        <h2>@if(!empty($sectionHeadings['section-5'])){{ $sectionHeadings['section-5'] }}@else
Thoughtfully curated
@endif</h2>
        <p>Trips worth looking forward to.</p>
      </div>
      <div>
        <i class="fa-solid fa-heart" aria-hidden="true"></i>
        <h2>Made for your memories</h2>
        <p>More moments that matter.</p>
      </div>
      <div>
        <i class="fa-solid fa-mobile-screen" aria-hidden="true"></i>
        <h2>Plan anywhere</h2>
        <p>From your phone or desktop.</p>
      </div>
    </section>
  </div>
@endsection

@section('footer')
  <footer class="booking-footer">
    <a href="{{ url('/') }}"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> TRAVEL</a>
    <p>© {{ date('Y') }} Travel. Around the world, with you.</p>
    <a href="{{ url('/about') }}">Get to know us <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
  </footer>
@endsection

@push('scripts')
  <script>
    window.BOOKING_PACKAGES = @json($packagesData);
    window.BOOKING_PLAN_URL = "{{ route('bookings.index') }}";
    window.BOOKING_REVIEW_URL = "{{ route('bookings.review') }}";
    window.BOOKING_READY_URL = "{{ route('bookings.ready') }}";
  </script>
  <script src="{{ asset('booking-flow.js') }}" defer></script>
  <script src="{{ asset('js/airport-select.js') }}" defer></script>
  <script src="{{ asset('bookings.js') }}" defer></script>
@endpush


