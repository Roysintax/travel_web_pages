@extends('layouts.app')

@section('title', $page?->title ?? 'Review Your Details — Travel')
@section('meta_description', $page?->meta_description ?? 'Plan your next Travel getaway, choose a destination, and review your trip estimate.')
@section('body_class', 'bookings-page')
@section('body_attrs') data-booking-step="2" @endsection
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
  <section class="booking-hero">
    <div class="mx-auto max-w-6xl px-6 booking-hero-inner">
      <div>
        <p class="booking-eyebrow">YOUR JOURNEY · STEP 2 OF 3</p>
        <h1>@if(!empty($sectionHeadings['section-1'])){{ $sectionHeadings['section-1'] }}@else
Let’s get the details right.
@endif</h1>
        <p>Take a moment to review your journey before the final preview.</p>
      </div>
    </div>
  </section>

  <div class="booking-container mx-auto max-w-6xl px-6">
    <ol class="booking-steps" aria-label="Booking preview steps">
      <li class="completed"><span>1</span>Plan your trip</li>
      <li class="current" aria-current="step"><span>2</span>Review your details</li>
      <li><span>3</span>Ready to explore</li>
    </ol>

    <section id="flow-empty" class="booking-panel flow-empty" hidden>
      <i class="fa-solid fa-suitcase" aria-hidden="true"></i>
      <h2>@if(!empty($sectionHeadings['flow-empty'])){{ $sectionHeadings['flow-empty'] }}@else
Your journey starts with a plan
@endif</h2>
      <p>There is no valid trip draft in this tab yet. Choose a package and fill in your details first.</p>
      <a class="review-button" href="{{ route('bookings.index') }}">Plan My Trip <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </section>

    <article id="flow-content" class="booking-panel flow-card" hidden>
      <header class="flow-card-heading">
        <span class="flow-icon"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></span>
        <p class="booking-eyebrow">ONE LAST LOOK</p>
        <h2>Your journey, at a glance</h2>
        <p>Check your trip and traveler details below.</p>
      </header>

      <div class="flow-trip-banner">
        <img id="flow-image" alt="" width="390" height="260">
        <div>
          <h3 id="flow-package"></h3>
          <p id="flow-duration"></p>
        </div>
      </div>

      <div class="flow-details-grid">
        <section aria-labelledby="journey-details-title">
          <h3 id="journey-details-title">Trip details</h3>
          <dl id="flow-trip-details" class="flow-details"></dl>
        </section>
        <section aria-labelledby="traveler-details-title">
          <h3 id="traveler-details-title">Lead traveler</h3>
          <dl id="flow-traveler-details" class="flow-details"></dl>
        </section>
      </div>

      <section id="flow-notes-section" class="flow-notes" hidden>
        <h3>Your travel notes</h3>
        <p id="flow-notes"></p>
      </section>

      <div class="flow-total">
        <div>Estimated total<small>IDR · All travelers · Indicative pricing</small></div>
        <strong id="flow-total"></strong>
      </div>

      <form id="continue-preview">
        <label class="flow-consent">
          <input id="review-acknowledgement" type="checkbox" required>
          <span>I’ve reviewed these details and understand this is a preview. No reservation or payment will be made.</span>
        </label>
        <p id="flow-error" class="flow-error" role="alert" hidden>Draft storage is unavailable. Return to your plan and try again.</p>
        <div class="flow-actions">
          <a class="flow-secondary" href="{{ route('bookings.index') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Edit My Trip</a>
          <button class="review-button" type="submit">Ready to Explore <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
        </div>
      </form>
    </article>
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
  <script src="{{ asset('booking-steps.js') }}" defer></script>
@endpush


