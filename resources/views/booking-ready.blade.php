@extends('layouts.app')

@section('title', $page?->title ?? 'Ready to Explore — Travel')
@section('meta_description', $page?->meta_description ?? 'Plan your next Travel getaway, choose a destination, and review your trip estimate.')
@section('body_class', 'bookings-page')
@section('body_attrs') data-booking-step="3" @endsection
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
        <p class="booking-eyebrow">YOUR JOURNEY · STEP 3 OF 3</p>
        <h1>@if(!empty($sectionHeadings['section-1'])){{ $sectionHeadings['section-1'] }}@else
Your next adventure awaits.
@endif</h1>
        <p>Your travel plan is ready to explore. Here’s what comes next.</p>
      </div>
    </div>
  </section>

  <div class="booking-container mx-auto max-w-6xl px-6">
    <ol class="booking-steps" aria-label="Booking preview steps">
      <li class="completed"><span>1</span>Plan your trip</li>
      <li class="completed"><span>2</span>Review your details</li>
      <li class="current" aria-current="step"><span>3</span>Ready to explore</li>
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
        <span class="flow-icon"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></span>
        <p class="booking-eyebrow">YOUR TRIP PREVIEW IS COMPLETE</p>
        <h2>All set to start dreaming</h2>
        <p>This is your completed travel preview, not a reservation.</p>
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

      <!-- Payment Method Selection (Cash or Payment Gateway) -->
      <section class="payment-section" aria-labelledby="payment-section-title">
        <header class="payment-section-header">
          <span class="payment-badge-tag"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Secure Transaction</span>
          <h3 id="payment-section-title">Pilih Metode Pembayaran</h3>
          <p>Pilih metode penyelesaian pembayaran untuk mengonfirmasi rencana liburan Anda.</p>
        </header>

        <div class="payment-options-grid" role="radiogroup" aria-label="Pilihan metode pembayaran">
          <!-- Opsi 1: Cash / Bayar di Kantor -->
          <label class="payment-option-card is-selected" id="option-card-cash">
            <input type="radio" name="payment_method" value="cash" checked>
            <div class="payment-option-inner">
              <div class="payment-option-head">
                <div class="payment-icon-wrap cash-icon">
                  <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                </div>
                <div class="payment-option-title-wrap">
                  <div class="flex items-center gap-2">
                    <h4>Cash / Bayar di Kantor</h4>
                    <span class="payment-tag tag-green">Direct Payment</span>
                  </div>
                  <p>Pelunasan tunai atau debit langsung di kantor cabang Travel Jakarta</p>
                </div>
                <i class="fa-solid fa-circle-check payment-check-icon" aria-hidden="true"></i>
              </div>

              <div class="payment-details-box cash-details">
                <ul class="payment-feature-bullets">
                  <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Jl. Jend. Sudirman Kav. 21, Jakarta Selatan (Dekat MRT Setiabudi Astra)</li>
                  <li><i class="fa-solid fa-clock" aria-hidden="true"></i> Buka Senin – Sabtu, 08:00 – 20:00 WIB</li>
                  <li><i class="fa-solid fa-receipt" aria-hidden="true"></i> Dapatkan bukti tanda terima resmi &amp; konsultasi jadwal gratis</li>
                </ul>
              </div>
            </div>
          </label>

          <!-- Opsi 2: Payment Gateway (Online) -->
          <label class="payment-option-card" id="option-card-gateway">
            <input type="radio" name="payment_method" value="gateway">
            <div class="payment-option-inner">
              <div class="payment-option-head">
                <div class="payment-icon-wrap gateway-icon">
                  <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                </div>
                <div class="payment-option-title-wrap">
                  <div class="flex items-center gap-2">
                    <h4>Payment Gateway (Midtrans Snap)</h4>
                    <span class="payment-tag tag-blue">Instant &amp; Automated</span>
                  </div>
                  <p>Pembayaran instan online via Virtual Account, QRIS &amp; Kartu Kredit</p>
                </div>
                <i class="fa-solid fa-circle-check payment-check-icon" aria-hidden="true"></i>
              </div>

              <div class="payment-details-box gateway-details">
                <div class="payment-channels-preview">
                  <span class="channel-chip"><i class="fa-brands fa-cc-visa" aria-hidden="true"></i> Visa</span>
                  <span class="channel-chip"><i class="fa-brands fa-cc-mastercard" aria-hidden="true"></i> Mastercard</span>
                  <span class="channel-chip"><i class="fa-solid fa-qrcode" aria-hidden="true"></i> QRIS</span>
                  <span class="channel-chip"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> BCA / Mandiri VA</span>
                  <span class="channel-chip"><i class="fa-solid fa-wallet" aria-hidden="true"></i> E-Wallet</span>
                </div>
                <p class="gateway-note">
                  <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Midtrans Snap Checkout · Verifikasi Realtime 24/7
                </p>
              </div>
            </div>
          </label>
        </div>

        <!-- Payment Action Button -->
        <div class="payment-cta-box">
          <div class="payment-summary-mini">
            <span>Total Biaya Paket:</span>
            <strong id="payment-display-total">Rp 0</strong>
          </div>
          <button type="button" id="btn-open-payment" class="payment-submit-btn">
            <i class="fa-solid fa-lock" aria-hidden="true"></i>
            <span id="btn-payment-label">Konfirmasi Bayar Tunai di Kantor</span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </button>
        </div>
      </section>

      <section class="flow-next">
        <h3>What’s next?</h3>
        <ul>
          <li><i class="fa-solid fa-compass" aria-hidden="true"></i> Explore more inspiration for your destination.</li>
          <li><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Check availability and final prices with your travel provider.</li>
          <li><i class="fa-solid fa-circle-info" aria-hidden="true"></i> No confirmation email, payment, or booking has been created.</li>
        </ul>
      </section>

      <div class="flow-actions">
        <a class="flow-secondary" href="{{ route('bookings.review') }}">Back to Review</a>
        <a class="review-button" href="{{ url('/destinations') }}">Explore Destinations <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </div>
      <button id="clear-preview" class="flow-clear" type="button">Clear Draft & Start Again</button>
    </article>

    <!-- Interactive Payment Modal Dialog -->
    <div id="payment-modal" class="payment-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modal-payment-title">
      <div class="payment-modal-box">
        <button type="button" id="payment-modal-close" class="payment-modal-close" aria-label="Tutup dialog">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>

        <header class="text-center mb-4">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-600 mb-2">
            <i class="fa-solid fa-receipt text-xl" aria-hidden="true"></i>
          </div>
          <h3 id="modal-payment-title" class="text-xl font-black text-slate-800">Detail Pembayaran</h3>
          <p id="modal-payment-subtitle" class="text-xs text-slate-500 mt-1">Konfirmasi langkah pembayaran untuk rencana perjalanan Anda.</p>
        </header>

        <!-- VIEW 1: CASH / BAYAR DI KANTOR -->
        <div id="modal-view-cash">
          <div class="modal-invoice-card">
            <div class="invoice-badge-bar">
              <span class="text-xs font-bold text-slate-600">INVOICE PREVIEW</span>
              <span class="invoice-code js-booking-code">TRV-2026-0000</span>
            </div>
            <dl class="invoice-rows">
              <div class="invoice-row">
                <dt>Paket Wisata</dt>
                <dd class="js-package-name">-</dd>
              </div>
              <div class="invoice-row">
                <dt>Nama Pemesan</dt>
                <dd class="js-traveler-name">-</dd>
              </div>
              <div class="invoice-row">
                <dt>Status Pembayaran</dt>
                <dd><span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800">Menunggu Pembayaran</span></dd>
              </div>
              <div class="invoice-row">
                <dt>Batas Waktu Pelunasan</dt>
                <dd>1 x 24 Jam di Kantor</dd>
              </div>
              <div class="invoice-row total-row">
                <dt>Total Pembayaran</dt>
                <dd class="js-booking-total">-</dd>
              </div>
            </dl>
          </div>

          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 space-y-2 mb-5">
            <p class="font-bold text-slate-800 flex items-center gap-2">
              <i class="fa-solid fa-circle-info text-blue-600"></i> Petunjuk Pembayaran di Kantor:
            </p>
            <p>1. Datangi kantor resmi Travel di <strong>Jl. Jend. Sudirman Kav. 21, Jakarta</strong>.</p>
            <p>2. Tunjukkan kode booking <strong class="text-blue-700 js-booking-code">TRV-2026-0000</strong> kepada Customer Service di meja reservasi.</p>
            <p>3. Pembayaran dapat menggunakan Tunai (Cash) atau Kartu Debit.</p>
          </div>

          <div class="flex flex-col sm:flex-row gap-3">
            <button type="button" id="btn-download-voucher" class="flow-secondary flex-1">
              <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Unduh Invoice Reservasi (PDF)
            </button>
            <button type="button" id="modal-done-btn" class="review-button flex-1" style="margin-top:0;">
              <i class="fa-solid fa-check" aria-hidden="true"></i> Selesai
            </button>
          </div>
        </div>

        <!-- VIEW 2: MIDTRANS SNAP CHECKOUT -->
        <div id="modal-view-gateway" hidden>
          <!-- State 1: Pilih Saluran & Bayar Sekarang -->
          <div id="gateway-sim-initial">
            <!-- Midtrans Header Bar -->
            <div class="flex items-center justify-between p-3.5 bg-gradient-to-r from-blue-900 to-indigo-900 text-white rounded-xl mb-4 shadow-sm">
              <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/20 text-blue-200 border border-blue-400/30">
                  <i class="fa-solid fa-bolt text-sm"></i>
                </span>
                <div>
                  <div class="text-[11px] uppercase tracking-wider font-extrabold text-blue-300">Midtrans Snap Checkout</div>
                  <div class="text-xs font-mono font-bold text-white js-booking-code">TRV-2026-0000</div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-[10px] text-blue-300 font-semibold">Total Pembayaran</div>
                <strong class="js-booking-total text-sm font-black text-white">-</strong>
              </div>
            </div>

            <!-- Saluran Pembayaran Tabs -->
            <p class="text-xs font-bold text-slate-700 mb-2 flex items-center justify-between">
              <span>Pilih Metode Pembayaran:</span>
              <span class="text-[11px] font-normal text-emerald-600 flex items-center gap-1">
                <i class="fa-solid fa-shield-halved"></i> PCI-DSS &amp; BI Compliant
              </span>
            </p>
            <div class="gateway-channel-tabs">
              <button type="button" class="gateway-tab-btn is-active" data-channel="qris">
                <i class="fa-solid fa-qrcode"></i> QRIS / GoPay
              </button>
              <button type="button" class="gateway-tab-btn" data-channel="va">
                <i class="fa-solid fa-building-columns"></i> Virtual Account
              </button>
              <button type="button" class="gateway-tab-btn" data-channel="cc">
                <i class="fa-solid fa-credit-card"></i> Kartu Kredit
              </button>
            </div>

            <!-- Panel QRIS -->
            <div id="channel-qris" class="gateway-panel-content gateway-channel-panel">
              <div class="qris-preview-box">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                  <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-black">QRIS RESMI</span>
                  <span>GoPay, OVO, Dana, ShopeePay, BCA Mobile</span>
                </div>
                <div class="qris-code-img flex items-center justify-center">
                  <svg width="150" height="150" viewBox="0 0 140 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="140" height="140" fill="white"/>
                    <rect x="10" y="10" width="40" height="40" fill="#0f172a" rx="4"/>
                    <rect x="18" y="18" width="24" height="24" fill="white" rx="2"/>
                    <rect x="24" y="24" width="12" height="12" fill="#0f172a" rx="1"/>
                    <rect x="90" y="10" width="40" height="40" fill="#0f172a" rx="4"/>
                    <rect x="98" y="18" width="24" height="24" fill="white" rx="2"/>
                    <rect x="104" y="24" width="12" height="12" fill="#0f172a" rx="1"/>
                    <rect x="10" y="90" width="40" height="40" fill="#0f172a" rx="4"/>
                    <rect x="18" y="98" width="24" height="24" fill="white" rx="2"/>
                    <rect x="24" y="104" width="12" height="12" fill="#0f172a" rx="1"/>
                    <rect x="58" y="14" width="10" height="10" fill="#0f172a"/>
                    <rect x="72" y="14" width="8" height="8" fill="#0f172a"/>
                    <rect x="58" y="32" width="14" height="8" fill="#0f172a"/>
                    <rect x="76" y="26" width="6" height="16" fill="#0f172a"/>
                    <rect x="14" y="58" width="8" height="12" fill="#0f172a"/>
                    <rect x="26" y="64" width="16" height="8" fill="#0f172a"/>
                    <rect x="46" y="58" width="10" height="10" fill="#0f172a"/>
                    <rect x="62" y="54" width="16" height="16" fill="#0284c7"/>
                    <rect x="84" y="58" width="12" height="8" fill="#0f172a"/>
                    <rect x="104" y="58" width="8" height="12" fill="#0f172a"/>
                    <rect x="118" y="64" width="12" height="8" fill="#0f172a"/>
                    <rect x="58" y="78" width="10" height="12" fill="#0f172a"/>
                    <rect x="74" y="76" width="10" height="8" fill="#0f172a"/>
                    <rect x="90" y="86" width="16" height="12" fill="#0f172a"/>
                    <rect x="112" y="82" width="14" height="16" fill="#0f172a"/>
                    <rect x="58" y="96" width="12" height="14" fill="#0f172a"/>
                    <rect x="76" y="102" width="10" height="12" fill="#0f172a"/>
                    <rect x="90" y="112" width="22" height="12" fill="#0f172a"/>
                    <rect x="120" y="104" width="10" height="20" fill="#0f172a"/>
                  </svg>
                </div>
                <div class="text-[11px] text-slate-500">
                  <span>NMID: <strong class="text-slate-700">ID1020304050607</strong></span> · 
                  <span>Merchant: <strong class="text-blue-700">Travel Official Indonesia</strong></span>
                </div>
                <p class="text-[11px] text-slate-600 bg-blue-50/80 p-2 rounded-lg border border-blue-100 max-w-sm">
                  Buka aplikasi m-Banking atau E-Wallet apa saja berlogo QRIS, lalu arahkan kamera ke kode QR di atas untuk bayar.
                </p>
              </div>
            </div>

            <!-- Panel Virtual Account -->
            <div id="channel-va" class="gateway-panel-content gateway-channel-panel" hidden>
              <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-bold text-slate-700">Nomor Virtual Account:</label>
                <span class="text-[11px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded">BCA / Mandiri / BRI / BNI</span>
              </div>
              <div class="va-copy-box">
                <span id="va-number-text" class="tracking-wider font-mono font-black text-sm">8801 2948 2019 4810</span>
                <button type="button" id="btn-copy-va" class="va-copy-btn">
                  <i class="fa-regular fa-copy"></i> Salin
                </button>
              </div>
              <div class="mt-3 text-[11px] text-slate-600 space-y-1.5 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                <p class="font-bold text-slate-700"><i class="fa-solid fa-circle-info text-blue-600"></i> Cara Pembayaran:</p>
                <p>1. Masuk ke m-Banking &gt; Transfer &gt; Virtual Account.</p>
                <p>2. Masukkan nomor VA di atas dan konfirmasi total tagihan.</p>
                <p>3. Verifikasi dengan PIN m-Banking Anda. Pembayaran otomatis terkonfirmasi.</p>
              </div>
            </div>

            <!-- Panel Kartu Kredit -->
            <div id="channel-cc" class="gateway-panel-content gateway-channel-panel" hidden>
              <div class="space-y-2.5 text-xs">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Nomor Kartu Kredit / Debit:</label>
                  <div class="relative">
                    <input type="text" id="cc-number-input" value="4111 2222 3333 4444" class="w-full p-2.5 pr-14 border border-slate-300 rounded text-xs bg-white font-mono tracking-wider" placeholder="4111 2222 3333 4444">
                    <span class="absolute right-2.5 top-2.5 flex items-center gap-1 text-slate-400">
                      <i class="fa-brands fa-cc-visa text-blue-700 text-sm"></i>
                      <i class="fa-brands fa-cc-mastercard text-orange-600 text-sm"></i>
                    </span>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block font-bold text-slate-700 mb-1">Masa Berlaku (MM/YY):</label>
                    <input type="text" id="cc-expiry-input" value="12/28" class="w-full p-2.5 border border-slate-300 rounded text-xs bg-white font-mono" placeholder="MM/YY">
                  </div>
                  <div>
                    <label class="block font-bold text-slate-700 mb-1">CVV / CVV2:</label>
                    <input type="password" id="cc-cvv-input" value="888" maxlength="4" class="w-full p-2.5 border border-slate-300 rounded text-xs bg-white font-mono" placeholder="•••">
                  </div>
                </div>
                <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                  <i class="fa-solid fa-shield-halved text-emerald-600"></i> Dilindungi 3D-Secure dengan otentikasi OTP SMS Bank penerbit.
                </p>
              </div>
            </div>

            <!-- Security Footer Note -->
            <div class="text-[11px] text-slate-600 mb-3 bg-slate-100 p-2.5 rounded-lg flex items-center gap-2">
              <i class="fa-solid fa-lock text-blue-600"></i>
              <span>Transaksi dilindungi enkripsi TLS 1.3 dan diproses oleh <strong>Midtrans Payment Gateway</strong>.</span>
            </div>

            <!-- Submit Button inside Modal -->
            <button type="button" id="btn-snap-pay-now" class="review-button w-full" style="margin-top:0;">
              <i class="fa-solid fa-bolt" aria-hidden="true"></i> Selesaikan Pembayaran (Midtrans)
            </button>
          </div>

          <!-- State 2: Status Pembayaran Berhasil (Official Midtrans Receipt) -->
          <div id="gateway-sim-success" class="payment-status-success" hidden>
            <div class="payment-success-icon">
              <i class="fa-solid fa-check" aria-hidden="true"></i>
            </div>
            <h4 id="payment-result-title" class="text-xl font-black text-slate-800">Pembayaran Berhasil!</h4>
            <p id="payment-result-description" class="text-xs text-slate-600 mt-1 max-w-sm" aria-live="polite">
              Transaksi Midtrans Snap telah diverifikasi dan lunas secara realtime.
            </p>
            <div class="modal-invoice-card w-full text-left mt-4 mb-4">
              <div class="invoice-badge-bar">
                <span class="text-xs font-bold text-slate-600">OFFICIAL MIDTRANS RECEIPT</span>
                <span class="invoice-code js-booking-code">TRV-2026-0000</span>
              </div>
              <dl class="invoice-rows">
                <div class="invoice-row">
                  <dt>Status Pembayaran</dt>
                  <dd><span id="payment-result-status" class="inline-block px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">PAID / LUNAS</span></dd>
                </div>
                <div class="invoice-row">
                  <dt>Metode Pembayaran</dt>
                  <dd id="receipt-channel">Midtrans Snap Checkout</dd>
                </div>
                <div class="invoice-row">
                  <dt>Nomor Transaksi</dt>
                  <dd class="font-mono text-xs text-slate-700" id="receipt-transaction-id">MIDTRANS-LIVE-OK</dd>
                </div>
                <div class="invoice-row">
                  <dt>Waktu Penyelesaian</dt>
                  <dd class="text-xs text-slate-700" id="receipt-timestamp">{{ date('d M Y, H:i') }} WIB</dd>
                </div>
                <div class="invoice-row total-row">
                  <dt>Total Terbayar</dt>
                  <dd class="js-booking-total text-emerald-700 font-black">-</dd>
                </div>
              </dl>
            </div>
            <div class="flex flex-col sm:flex-row gap-2.5 w-full">
              <button type="button" id="btn-download-receipt" class="flow-secondary flex-1">
                <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Unduh E-Receipt
              </button>
              <button type="button" id="btn-receipt-done" class="review-button flex-1" style="margin-top:0;">
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Selesai &amp; Tutup
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
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
  @php
    $snapUrl = config('midtrans.is_production')
      ? 'https://app.midtrans.com/snap/snap.js'
      : 'https://app.sandbox.midtrans.com/snap/snap.js';
  @endphp
  <script src="{{ $snapUrl }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
  <script>
    window.BOOKING_PACKAGES = @json($packagesData);
    window.BOOKING_PLAN_URL = "{{ route('bookings.index') }}";
    window.BOOKING_REVIEW_URL = "{{ route('bookings.review') }}";
    window.BOOKING_READY_URL = "{{ route('bookings.ready') }}";
    window.CURRENT_BOOKING_CODE = @json($latestBooking?->reference_code);
    window.CURRENT_BOOKING_STATUS = @json($latestBooking?->payment_status ?? 'unpaid');
    window.PAYMENT_SIMULATION_ENABLED = @json(\App\Services\MidtransService::allowsSimulation(request()));
    window.MIDTRANS_CLIENT_KEY = @json(config('midtrans.client_key'));
  </script>
  <script src="{{ asset('booking-flow.js') }}" defer></script>
  <script src="{{ asset('booking-steps.js') }}" defer></script>
  <script src="{{ asset('booking-payment.js') }}" defer></script>
@endpush


