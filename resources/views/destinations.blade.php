@extends('layouts.app')

@section('title', $page?->title ?? 'Destinations — Travel Around the World')
@section('meta_description', $page?->meta_description ?? 'From peaceful island mornings to mountain adventures, discover extraordinary destinations around the world.')
@section('body_class', 'destinations-page')
@section('nav', 'destinations')

@push('styles')
  <link rel="stylesheet" href="{{ asset('destinations.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
@endpush

@push('scripts')
  <script src="{{ asset('destinations.js') }}" defer></script>
@endpush

@section('content')
  <section class="destination-hero" aria-labelledby="destination-title">
    <video class="hero-video" autoplay muted loop playsinline preload="auto" aria-hidden="true" tabindex="-1">
      <source src="{{ asset('gemini_generated_video_c9ca0f42.mp4') }}" type="video/mp4">
    </video>
    <div class="mx-auto max-w-6xl px-6 destination-hero-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="{{ url('/') }}">Home</a></li>
          <li><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></li>
          <li aria-current="page">Destinations</li>
        </ol>
      </nav>
      <p class="eyebrow"><i class="fa-solid fa-compass fa-spin" aria-hidden="true"></i> A world of possibilities</p>
      <h1 id="destination-title">@if(!empty($sectionHeadings['section-1'])){{ $sectionHeadings['section-1'] }}@else
Find your next<br><span>great escape.</span>
@endif</h1>
      <p class="handwriting">Explore. Dream. Discover.</p>
      <p class="hero-description">From peaceful island mornings to mountain adventures,<br class="hidden sm:block"> discover a place that feels like you.</p>
      <a href="#explore" class="hero-explore">Explore Destinations <i class="fa-solid fa-location-dot fa-bounce" aria-hidden="true"></i></a>
    </div>
  </section>

  <div class="mx-auto max-w-6xl px-5 destination-main">
    <section id="explore" aria-labelledby="explore-title" class="surface destination-explore">
      <div class="explore-heading">
        <div>
          <p class="eyebrow">YOUR NEXT CHAPTER</p>
          <h2 id="explore-title">@if(!empty($sectionHeadings['explore'])){{ $sectionHeadings['explore'] }}@else
Where will you go?
@endif</h2>
        </div>
        <p>Extraordinary places. Unforgettable memories.</p>
      </div>

      <form id="destination-filters" class="destination-filters" role="search" aria-label="Search destinations">
        <label class="destination-search">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <span class="sr-only">Search by destination or country</span>
          <input id="destination-query" type="search" placeholder="Search destination or country…" autocomplete="off">
        </label>
        <label class="region-select">
          <span class="sr-only">Filter by region</span>
          <select id="destination-region">
            <option value="all">All regions</option>
            @foreach ($regions as $region)
              <option value="{{ $region }}">{{ $region }}</option>
            @endforeach
          </select>
        </label>
        <button type="reset" class="reset-filters">
          <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Reset
        </button>
      </form>

      <p id="destination-count" class="result-count" role="status" aria-live="polite">{{ count($destinations) }} destination{{ count($destinations) === 1 ? '' : 's' }} to explore</p>

      <div class="destination-grid">
        @forelse ($destinations as $dest)
          <article class="destination-card floating-card" data-region="{{ $dest->region }}" data-search="{{ $dest->search_keywords }}">
            <img class="destination-cover" src="{{ asset($dest->image?->file_path ?? 'assets/hero.png') }}" alt="{{ $dest->image?->alt_text ?: $dest->name }}" loading="lazy" width="390" height="260">
            <span class="destination-badge">{{ $dest->badge_label }}</span>
            <div class="destination-overlay">
              <p class="overlay-region"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $dest->region }}</p>
              <h3><a href="{{ url('/bookings?destination=' . urlencode($dest->name)) }}">{{ $dest->name }}</a></h3>
              @if ($dest->primaryPackage)
                <p class="overlay-price">Starting from <strong>{{ $dest->primaryPackage->formatted_price }}</strong> / person</p>
                @if ($dest->primaryPackage->rating)
                  <p class="overlay-rating"><span aria-hidden="true">★★★★★</span><span class="sr-only">Rated </span> {{ $dest->primaryPackage->rating }}<span class="sr-only"> out of 5</span></p>
                @endif
              @endif
            </div>
          </article>
        @empty
          <p class="text-sm text-slate-500 py-6">No destinations found in catalog.</p>
        @endforelse
      </div>

      <div id="destination-empty" class="empty-state" hidden>
        <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
        <h3>No destinations found</h3>
        <p>Try another country, destination, or region.</p>
        <button type="button" id="clear-filters" class="primary">Show all destinations</button>
      </div>
    </section>

    <section class="weekly-deals" aria-labelledby="deals-title">
      <div class="deals-promo floating-card">
        <div class="promo-copy">
          <p class="eyebrow">MAKE MEMORIES</p>
          <h2 id="deals-title">@if(!empty($sectionHeadings['section-3'])){{ $sectionHeadings['section-3'] }}@else
Top Trips<br>This Week
@endif</h2>
          <p>Your next adventure.<br>A little closer than you think.</p>
          <a href="{{ url('/packages') }}" class="hero-explore">Explore Trips <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <i class="fa-solid fa-earth-americas promo-globe" aria-hidden="true"></i>
        <i class="fa-solid fa-plane promo-plane" aria-hidden="true"></i>
      </div>

      @foreach ($topDeals as $deal)
        <article class="trip-deal floating-card">
          <div class="trip-deal-photo">
            <img src="{{ asset($deal->image?->file_path ?? 'assets/hero.png') }}" alt="{{ $deal->title }}" loading="lazy" width="390" height="260">
            <span class="trip-deal-badge">{{ $deal->badge ?? 'Top Deal' }}</span>
          </div>
          <div class="trip-deal-content">
            <h3>{{ $deal->title }}</h3>
            <p>{{ $deal->days }} Days / {{ $deal->nights }} Nights</p>
            <strong>{{ $deal->formatted_price }}</strong><small> / person</small>
            <a href="{{ url('/bookings?package=' . urlencode($deal->title)) }}" aria-label="Book {{ $deal->title }}">View Details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
          </div>
        </article>
      @endforeach
    </section>

    <section class="destination-reasons" aria-labelledby="reasons-title">
      <h2 id="reasons-title" class="sr-only">@if(!empty($sectionHeadings['section-4'])){{ $sectionHeadings['section-4'] }}@else
A little more peace of mind
@endif</h2>
      <article>
        <div class="paper-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
        <div>
          <h3>Travel with confidence</h3>
          <p>Carefully selected stays and experiences.</p>
        </div>
      </article>
      <article>
        <div class="paper-icon"><i class="fa-solid fa-heart fa-beat" aria-hidden="true"></i></div>
        <div>
          <h3>Made for your memories</h3>
          <p>Thoughtful trips, from start to finish.</p>
        </div>
      </article>
      <article>
        <div class="paper-icon"><i class="fa-solid fa-headset" aria-hidden="true"></i></div>
        <div>
          <h3>Here whenever you need</h3>
          <p>Friendly support throughout your journey.</p>
        </div>
      </article>
    </section>

    <section class="destination-cta" aria-labelledby="cta-title">
      <div>
        <p class="eyebrow">LET’S MAKE IT HAPPEN</p>
        <h2 id="cta-title">@if(!empty($sectionHeadings['section-5'])){{ $sectionHeadings['section-5'] }}@else
Your dream trip starts here.
@endif</h2>
        <p>Choose your destination. We’ll help with the rest.</p>
      </div>
      <a class="hero-explore" href="{{ url('/bookings') }}">Plan My Trip <i class="fa-solid fa-paper-plane" aria-hidden="true"></i></a>
    </section>
  </div>
@endsection


