@extends('layouts.app')

@section('title', $page?->title ?? 'Contact Us — Travel Around the World')
@section('meta_description', $page?->meta_description ?? 'Contact Travel: call, email, visit our office, or chat live with a travel expert to plan your next holiday.')
@section('body_class', 'contact-page')
@section('nav', 'contact')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/contact.css') }}">
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
        <li><a href="{{ url('/') }}">Home</a></li>
        <li><a href="{{ url('/packages') }}">Packages</a></li>
        <li><a href="{{ url('/destinations') }}">Destinations</a></li>
        <li><a href="{{ url('/bookings') }}">Bookings</a></li>
        <li><a href="{{ url('/about') }}">About Us</a></li>
        <li><a class="active" aria-current="page" href="{{ url('/contact') }}">Contact</a></li>
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
  <!-- Hero -->
  <section class="contact-hero" aria-labelledby="contact-title">
    <div class="contact-hero-art" aria-hidden="true"></div>
    <div class="mx-auto max-w-6xl px-6 contact-hero-content">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="{{ url('/') }}">Home</a></li>
          <li><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></li>
          <li aria-current="page">Contact</li>
        </ol>
      </nav>
      <p class="eyebrow"><span class="live-dot" aria-hidden="true"></span> Travel experts online now</p>
      <h1 id="contact-title">@if(!empty($sectionHeadings['section-1'])){{ $sectionHeadings['section-1'] }}@else
Let’s plan it<br><span>together.</span>
@endif</h1>
      <p class="handwriting">Ask. Chat. Get going.</p>
      <p class="hero-description">Questions about a destination, a package, or your booking?<br class="hidden sm:block"> Reach us any way you like — we usually reply in minutes.</p>
      <div class="hero-actions">
        <a href="#live-chat" class="hero-explore" data-open-chat>Start a Live Chat <i class="fa-solid fa-comments" aria-hidden="true"></i></a>
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings['phone']) }}" class="hero-ghost"><i class="fa-solid fa-phone" aria-hidden="true"></i> {{ $settings['phone'] }}</a>
      </div>
      <ul class="hero-stats" aria-label="Support at a glance">
        <li><strong>&lt; 2 min</strong><span>Average chat reply</span></li>
        <li><strong>24/7</strong><span>Trip support</span></li>
        <li><strong>4.9<i class="fa-solid fa-star" aria-hidden="true"></i></strong><span>Traveler rating</span></li>
      </ul>
    </div>
  </section>

  <div class="mx-auto max-w-6xl px-5 contact-main">
    <!-- Contact channels -->
    <section class="channel-grid" aria-labelledby="channels-title">
      <h2 id="channels-title" class="sr-only">@if(!empty($sectionHeadings['section-2'])){{ $sectionHeadings['section-2'] }}@else
Ways to reach us
@endif</h2>
      <article class="channel-card reveal">
        <span class="channel-icon icon-blue"><i class="fa-solid fa-phone-volume" aria-hidden="true"></i></span>
        <h3>Call us</h3>
        <p>{{ $settings['office_hours'] }}</p>
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings['phone']) }}">{{ $settings['phone'] }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </article>
      <article class="channel-card reveal">
        <span class="channel-icon icon-green"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></span>
        <h3>WhatsApp</h3>
        <p>Quick answers on the go</p>
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings['whatsapp']) }}" target="_blank" rel="noopener">{{ $settings['whatsapp'] }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </article>
      <article class="channel-card reveal">
        <span class="channel-icon icon-peach"><i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i></span>
        <h3>Email</h3>
        <p>Detailed plans &amp; documents</p>
        <a href="mailto:{{ $settings['email'] }}">{{ $settings['email'] }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </article>
      <article class="channel-card reveal">
        <span class="channel-icon icon-violet"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
        <h3>Visit us</h3>
        <p>{{ $settings['office_address'] }}</p>
        <a href="#office">See office hours <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </article>
    </section>

    <!-- Form + Live chat -->
    <section class="contact-workspace" aria-label="Send a message or chat live">
      <div class="surface contact-form-card reveal">
        <p class="eyebrow">SEND A MESSAGE</p>
        <h2 id="form-title">@if(!empty($sectionHeadings['section-3'])){{ $sectionHeadings['section-3'] }}@else
Tell us about your trip
@endif</h2>
        <p class="card-lead">Share a few details and a travel expert will get back to you within one business day.</p>

        @if (session('success'))
          <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-emerald-400">
            <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
          </div>
        @endif

        <form id="contact-form" class="contact-form" action="{{ route('contact.store') }}" method="POST" novalidate aria-labelledby="form-title">
          @csrf
          <div class="field-row">
            <div class="field @error('name') has-error @enderror">
              <label for="contact-name">Full name</label>
              <div class="input-wrap">
                <i class="fa-regular fa-user" aria-hidden="true"></i>
                <input id="contact-name" name="name" type="text" autocomplete="name" placeholder="Your name" value="{{ old('name') }}" required aria-describedby="contact-name-error">
              </div>
              <p class="field-error" id="contact-name-error">@error('name') {{ $message }} @enderror</p>
            </div>
            <div class="field @error('email') has-error @enderror">
              <label for="contact-email">Email</label>
              <div class="input-wrap">
                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                <input id="contact-email" name="email" type="email" autocomplete="email" placeholder="you@example.com" value="{{ old('email') }}" required aria-describedby="contact-email-error">
              </div>
              <p class="field-error" id="contact-email-error">@error('email') {{ $message }} @enderror</p>
            </div>
          </div>
          <div class="field-row">
            <div class="field @error('phone') has-error @enderror">
              <label for="contact-phone">Phone <span class="optional">(optional)</span></label>
              <div class="input-wrap">
                <i class="fa-solid fa-mobile-screen" aria-hidden="true"></i>
                <input id="contact-phone" name="phone" type="tel" autocomplete="tel" placeholder="+62 812 3456 7890" value="{{ old('phone') }}">
              </div>
            </div>
            <div class="field @error('topic') has-error @enderror">
              <label for="contact-topic">Topic</label>
              <div class="input-wrap select-wrap">
                <i class="fa-solid fa-tag" aria-hidden="true"></i>
                <select id="contact-topic" name="topic">
                  <option value="Plan a new trip" {{ old('topic') === 'Plan a new trip' ? 'selected' : '' }}>Plan a new trip</option>
                  <option value="Existing booking" {{ old('topic') === 'Existing booking' ? 'selected' : '' }}>Existing booking</option>
                  <option value="Packages & pricing" {{ old('topic') === 'Packages & pricing' ? 'selected' : '' }}>Packages &amp; pricing</option>
                  <option value="Visa & documents" {{ old('topic') === 'Visa & documents' ? 'selected' : '' }}>Visa &amp; documents</option>
                  <option value="Something else" {{ old('topic') === 'Something else' ? 'selected' : '' }}>Something else</option>
                </select>
              </div>
            </div>
          </div>
          <fieldset class="field">
            <legend>Preferred reply</legend>
            <div class="chip-group">
              <label class="chip">
                <input type="radio" name="reply" value="Email" {{ old('reply', 'Email') === 'Email' ? 'checked' : '' }}>
                <span><i class="fa-regular fa-envelope" aria-hidden="true"></i> Email</span>
              </label>
              <label class="chip">
                <input type="radio" name="reply" value="WhatsApp" {{ old('reply') === 'WhatsApp' ? 'checked' : '' }}>
                <span><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</span>
              </label>
              <label class="chip">
                <input type="radio" name="reply" value="Phone call" {{ old('reply') === 'Phone call' ? 'checked' : '' }}>
                <span><i class="fa-solid fa-phone" aria-hidden="true"></i> Phone call</span>
              </label>
            </div>
          </fieldset>
          <div class="field @error('message') has-error @enderror">
            <label for="contact-message">Message</label>
            <textarea id="contact-message" name="message" rows="5" maxlength="600" placeholder="Where would you like to go, when, and with how many travelers?" required aria-describedby="contact-message-error contact-message-count">{{ old('message') }}</textarea>
            <div class="field-meta">
              <p class="field-error" id="contact-message-error">@error('message') {{ $message }} @enderror</p>
              <span id="contact-message-count" class="char-count">0 / 600</span>
            </div>
          </div>
          <button type="submit" class="primary submit-button">
            <span>Send Message</span> <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
          </button>
          <p class="form-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Your inquiry is securely delivered to our travel consultant team.</p>
        </form>

        <div id="form-success" class="form-success" hidden tabindex="-1">
          <span class="success-icon"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
          <h3>Message received!</h3>
          <p id="form-success-text">Thanks — we’ll be in touch soon.</p>
          <button type="button" id="form-reset" class="text-button"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Send another message</button>
        </div>
      </div>

      <!-- Live chat -->
      <div id="live-chat" class="chat-card reveal" aria-labelledby="chat-title">
        <header class="chat-header">
          <div class="chat-agent">
            <span class="agent-avatar" aria-hidden="true">NA<span class="agent-status"></span></span>
            <div>
              <h2 id="chat-title">Nadia · AI Travel Assistant</h2>
              <p id="chat-presence"><span class="live-dot" aria-hidden="true"></span> Online · AI-powered replies</p>
            </div>
          </div>
          <div class="chat-tools">
            <button type="button" id="chat-clear" class="chat-tool" aria-label="Clear conversation" title="Clear conversation"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
          </div>
        </header>

        <div id="chat-log" class="chat-log" role="log" aria-live="polite" aria-label="Chat messages" tabindex="0"></div>

        <div class="chat-typing" id="chat-typing" hidden aria-hidden="true">
          <span class="agent-avatar small">NA</span>
          <span class="typing-bubble"><i></i><i></i><i></i></span>
        </div>

        <div class="quick-replies" id="quick-replies" aria-label="Suggested questions">
          <button type="button" data-quick="What packages do you have?">Packages</button>
          <button type="button" data-quick="Can you help with my booking?">My booking</button>
          <button type="button" data-quick="Do I need a visa?">Visa help</button>
          <button type="button" data-quick="What payment methods do you accept?">Payment</button>
          <button type="button" data-quick="I want to talk to a human agent.">Talk to agent</button>
        </div>

        <form id="chat-form" class="chat-form" autocomplete="off">
          <label for="chat-input" class="sr-only">Type your message</label>
          <textarea id="chat-input" rows="1" maxlength="1000" placeholder="Type a message…" required></textarea>
          <button type="submit" id="chat-send" class="chat-send" aria-label="Send message" disabled><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
        </form>
        <p class="chat-footnote">AI can make mistakes — confirm bookings with our team · <kbd>Enter</kbd> to send, <kbd>Shift</kbd>+<kbd>Enter</kbd> new line</p>
      </div>
    </section>

    <!-- Office -->
    <section id="office" class="office-section reveal" aria-labelledby="office-title">
      <div class="office-info">
        <p class="eyebrow">OUR OFFICE</p>
        <h2 id="office-title">@if(!empty($sectionHeadings['office'])){{ $sectionHeadings['office'] }}@else
Drop by for a coffee &amp; a plan.
@endif</h2>
        <ul class="office-list">
          <li>
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            <div>
              <strong>Address</strong>
              <span>{{ $settings['office_address'] }}</span>
            </div>
          </li>
          <li>
            <i class="fa-regular fa-clock" aria-hidden="true"></i>
            <div>
              <strong>Opening hours</strong>
              <span id="office-hours-status" class="hours-status">{{ $settings['office_hours'] }}</span>
            </div>
          </li>
          <li>
            <i class="fa-solid fa-train-subway" aria-hidden="true"></i>
            <div>
              <strong>Getting here</strong>
              <span>2 min walk from MRT Setiabudi Astra</span>
            </div>
          </li>
        </ul>
        <a class="hero-explore" href="https://www.openstreetmap.org/?mlat=-6.2146&amp;mlon=106.8213#map=16/-6.2146/106.8213" target="_blank" rel="noopener">Get Directions <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i></a>
      </div>
      <div class="office-map">
        <iframe title="Map showing the Travel office in Jakarta" src="https://www.openstreetmap.org/export/embed.html?bbox=106.8143%2C-6.2196%2C106.8283%2C-6.2096&amp;layer=mapnik&amp;marker=-6.2146%2C106.8213" loading="lazy"></iframe>
      </div>
    </section>

    <!-- CTA -->
    <section class="contact-cta" aria-labelledby="cta-title">
      <div>
        <p class="eyebrow">STILL DREAMING?</p>
        <h2 id="cta-title">@if(!empty($sectionHeadings['section-5'])){{ $sectionHeadings['section-5'] }}@else
Find a place that feels like you.
@endif</h2>
        <p>Browse destinations, then chat with us to make it happen.</p>
      </div>
      <a class="hero-explore" href="{{ url('/destinations') }}">Explore Destinations <i class="fa-solid fa-earth-asia" aria-hidden="true"></i></a>
    </section>
  </div>
@endsection

@section('footer')
  <footer class="contact-footer">
    <a href="{{ url('/') }}" class="footer-brand"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> TRAVEL</a>
    <p>© 2026 Travel. Around the world, with you.</p>
    <nav aria-label="Footer navigation">
      <a href="{{ url('/destinations') }}">Destinations</a>
      <a href="{{ url('/about') }}">About Us</a>
      <a href="{{ url('/contact') }}" aria-current="page">Contact</a>
    </nav>
  </footer>

  <!-- Floating launcher: jumps to the live chat from anywhere on the page -->
  <a href="#live-chat" class="chat-launcher" data-open-chat aria-label="Open live chat"><i class="fa-solid fa-comment-dots" aria-hidden="true"></i><span>Chat with us</span></a>
@endsection

@push('scripts')
  <script src="{{ asset('js/contact.js') }}" defer></script>
@endpush


