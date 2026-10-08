@extends('layouts.app')

@section('title', $page?->title ?? 'About Us — Travel Around the World')
@section('meta_description', $page?->meta_description ?? 'Meet Travel: a brighter way to discover destinations and bring your next holiday together.')
@section('body_class', 'about-page')
@section('nav', 'about')

@push('styles')
  <link rel="stylesheet" href="{{ asset('about.css') }}">
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
          <a href="{{ url('/bookings') }}">
            Bookings
          </a>
        </li>
        <li>
          <a class="active" aria-current="page" href="{{ url('/about') }}">
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
  <section class="about-hero" aria-labelledby="about-heading">
    <h1 id="about-heading" class="sr-only">@if(!empty($sectionHeadings['section-1'])){{ $sectionHeadings['section-1'] }}@else
About Travel — Holiday made simple, smart and connected
@endif</h1>
    <figure class="about-hero-art about-video-art">
      <video id="about-background-video" class="about-background-video" autoplay muted loop playsinline disablepictureinpicture disableremoteplayback tabindex="-1" preload="metadata" poster="{{ asset('assets/about/holiday-full.png') }}" aria-label="Travel holiday background video">
        <source src="{{ asset('assets/about/holiday-background.mp4') }}" type="video/mp4">
        Your browser does not support this video.
      </video>
    </figure>
    <div class="about-hero-action">
      <a href="{{ url('/destinations') }}" class="about-button">Discover Your Next Trip <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>

  <div class="about-container mx-auto max-w-6xl px-6">
    <ul class="travel-moments" aria-label="Your travel journey">
      <li><i class="fa-solid fa-plane" aria-hidden="true"></i> Explore</li>
      <li><i class="fa-regular fa-calendar" aria-hidden="true"></i> Plan</li>
      <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Book</li>
      <li><i class="fa-solid fa-star" aria-hidden="true"></i> Travel</li>
      <li><i class="fa-solid fa-heart" aria-hidden="true"></i> Discover</li>
    </ul>

    <section class="about-story reveal" aria-labelledby="story-heading">
      <div>
        <p class="pill">OUR STORY</p>
        <h2 id="story-heading">@if(!empty($sectionHeadings['section-2'])){{ $sectionHeadings['section-2'] }}@else
Your entire journey.<br>A little more connected.
@endif</h2>
      </div>
      <div>
        <p>Travel begins with a simple question: where could we go next? We created this space to turn that spark of curiosity into a journey worth remembering.</p>
        <p>From quiet island mornings to new cities and mountain horizons, our purpose is to make discovering your next holiday feel welcoming, inspiring, and easy.</p>
        <a class="text-link" href="{{ url('/bookings') }}">Start your journey <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </div>
    </section>

    <section class="about-features" aria-labelledby="features-heading">
      <header class="section-intro reveal">
        <p class="pill"><span aria-hidden="true">●</span> YOUR NEXT ADVENTURE AWAITS</p>
        <h2 id="features-heading">@if(!empty($sectionHeadings['section-3'])){{ $sectionHeadings['section-3'] }}@else
Thoughtful travel.<br>Unforgettable holidays.
@endif</h2>
        <p>A little inspiration. A clearer plan. More time for what matters.</p>
      </header>

      <div class="feature-grid">
        <article class="feature-card feature-mint reveal">
          <img src="{{ asset('assets/about/destinations.jpg') }}" alt="A traveler in a sun hat overlooking a tropical beach" loading="lazy" width="304" height="280">
          <div>
            <h3>Discover Dream Destinations</h3>
            <p>Explore island escapes, vibrant cities, and incredible landscapes. Find a place that speaks to your sense of adventure.</p>
            <a href="{{ url('/destinations') }}" aria-label="Discover our destinations"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
          </div>
        </article>

        <article class="feature-card feature-blue reveal">
          <img src="{{ asset('assets/about/planning.jpg') }}" alt="Passport, map, and camera with an airplane above a tropical coast" loading="lazy" width="294" height="280">
          <div>
            <h3>A Clearer Way to Plan</h3>
            <p>Compare destinations and trip lengths, gather ideas, and choose a package that fits the holiday you have in mind.</p>
            <a href="{{ url('/packages') }}" aria-label="Explore travel packages"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
          </div>
        </article>

        <article class="feature-card feature-peach reveal">
          <img src="{{ asset('assets/about/booking.jpg') }}" alt="An infinity pool and sun loungers overlooking a blue bay" loading="lazy" width="303" height="280">
          <div>
            <h3>Make Every Moment Count</h3>
            <p>Less time searching, more time imagining your getaway. Start with our trip preview and take the next step at your pace.</p>
            <a href="{{ url('/bookings') }}" aria-label="Open the booking preview"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
          </div>
        </article>
      </div>
    </section>
  </div>

  <section class="about-mobile" aria-labelledby="mobile-heading">
    <div class="about-mobile-inner mx-auto max-w-6xl px-6">
      <div class="mobile-copy reveal">
        <p class="pill">YOUR HOLIDAY, IN YOUR HANDS</p>
        <h2 id="mobile-heading">@if(!empty($sectionHeadings['section-4'])){{ $sectionHeadings['section-4'] }}@else
A world of possibility.<br>Wherever you are.
@endif</h2>
        <p>Explore destinations, discover packages, and enjoy the same cinematic journey on your phone. Your next adventure is just a tap away.</p>
        <a class="about-button dark-button" href="{{ url('/destinations') }}">Start Exploring <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        <p class="mobile-note">Made for your browser, on every screen.</p>
      </div>
      <figure class="mobile-illustration reveal">
        <img src="{{ asset('assets/about/mobile.jpg') }}" alt="A hand holding a phone with a travel discovery screen beside a sunny coastal village" width="490" height="610" loading="lazy">
      </figure>
    </div>
  </section>

  <section class="about-faq about-container mx-auto max-w-6xl px-6" aria-labelledby="faq-heading">
    <header class="section-intro reveal">
      <p class="pill">TRAVEL FAQ</p>
      <h2 id="faq-heading">@if(!empty($sectionHeadings['section-5'])){{ $sectionHeadings['section-5'] }}@else
A little clarity.<br>A better journey.
@endif</h2>
      <p>Answers to help you get to know Travel and start exploring.</p>
    </header>

    <div class="faq-list">
      @foreach ($faqs as $faq)
        <details>
          <summary>{{ $faq->question }}<i class="fa-solid fa-plus" aria-hidden="true"></i></summary>
          <p>{{ $faq->answer }}</p>
        </details>
      @endforeach
    </div>

    <div class="about-closing reveal">
      <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
      <h3>Every journey starts with a little curiosity.</h3>
      <a href="{{ url('/destinations') }}" class="text-link">Find your next escape <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>
@endsection

@section('footer')
  <footer class="about-footer">
    <a href="{{ url('/') }}" class="footer-brand"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> TRAVEL</a>
    <p>© {{ date('Y') }} Travel. Around the world, with you.</p>
    <nav aria-label="Footer navigation">
      <a href="{{ url('/destinations') }}">Destinations</a>
      <a href="{{ url('/about') }}" aria-current="page">About Us</a>
      <a href="{{ url('/bookings') }}">Plan a trip</a>
    </nav>
  </footer>
@endsection

@push('scripts')
  <script src="{{ asset('about.js') }}" defer></script>
@endpush


