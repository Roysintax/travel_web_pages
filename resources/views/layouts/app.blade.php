<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Travel — Around the World')</title>
    @hasSection('meta_description')
    <meta name="description" content="@yield('meta_description')">
    @endif
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="{{ asset('styles.css') }}">
    @stack('styles')
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&family=Caveat:wght@500&display=swap" rel="stylesheet">
  </head>
  <body class="@yield('body_class')" @yield('body_attrs')>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <svg aria-hidden="true" class="svg-library">
      <defs>
        <symbol id="plane" viewBox="0 0 100 85">
          <path fill="#fff" d="M3 32 97 3 64 80 44 54Z"/>
          <path fill="#cfdfef" d="m44 54 53-51-40 59 7 18Z"/>
          <path fill="#88b3d8" d="m44 54-3 20 16-12L97 3Z"/>
          <path fill="#edf5fc" d="m3 32 94-29-65 40Z"/>
        </symbol>
      </defs>
    </svg>
    @php
      $nav = [
        ['home', 'Home', url('/')],
        ['packages', 'Packages', url('/packages')],
        ['destinations', 'Destinations', url('/destinations')],
        ['bookings', 'Bookings', url('/bookings')],
        ['about', 'About Us', url('/about')],
        ['contact', 'Contact', url('/contact')],
      ];
      $activeNav = trim($__env->yieldContent('nav', 'home'));
    @endphp
    @section('header')
    <header class="site-header mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-6">
      <a href="{{ url('/') }}" class="logo flex items-center gap-2">
        <svg class="h-14 w-14"><use href="#plane"/></svg>
        <span class="text-3xl tracking-wider">
          TRAVEL
          <small class="block text-[9px] font-extrabold tracking-normal">AROUND THE WORLD</small>
        </span>
      </a>
      <nav id="primary-navigation" aria-label="Main navigation" class="site-nav">
        <ul class="nav-list">
          @foreach ($nav as [$key, $label, $href])
            <li><a @class(['active' => $activeNav === $key]) href="{{ $href }}">{{ $label }}</a></li>
          @endforeach
        </ul>
      </nav>
      <a href="{{ url('/bookings') }}" class="header-booking rounded-full border border-white px-5 py-2 text-sm">Book a Trip ♙</a>
      <button class="menu-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" aria-label="Open navigation">
        <span></span><span></span><span></span>
      </button>
    </header>
    @show
    <main id="main-content">
      @yield('content')
    </main>
    @section('footer')
    <footer class="site-footer py-6 text-center text-xs text-slate-500">
      © {{ date('Y') }} Travel. Around the world, with you.
    </footer>
    @show
    @stack('scripts')
    <script src="{{ asset('script.js') }}" defer></script>
  </body>
</html>
