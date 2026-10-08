<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminResourceController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\Api\AirportController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\AdminAuthentication;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1,contact')->name('contact.store');

Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:10,1,bookings')->name('bookings.store');
Route::get('/booking-review', [BookingController::class, 'review'])->name('bookings.review');
Route::get('/booking-ready', [BookingController::class, 'ready'])->name('bookings.ready');
Route::patch('/bookings/{referenceCode}/status', [BookingController::class, 'updateStatus'])->middleware('throttle:30,1,booking-status')->name('bookings.update-status');
Route::post('/bookings/{referenceCode}/payment', [PaymentController::class, 'create'])->middleware('throttle:15,1,payment-create')->name('bookings.payment.create');
Route::post('/bookings/{referenceCode}/confirm', [PaymentController::class, 'confirm'])->middleware('throttle:15,1,payment-confirm')->name('bookings.payment.confirm');
Route::get('/bookings/{referenceCode}/payment-status', [PaymentController::class, 'status'])->middleware('throttle:30,1,payment-status')->name('bookings.payment.status');
Route::post('/bookings/{referenceCode}/payment-simulation', [PaymentController::class, 'simulate'])->middleware('throttle:15,1,payment-simulation')->name('bookings.payment.simulation');

// Midtrans Payment Gateway Webhook
Route::post('/payments/midtrans/webhook', [MidtransWebhookController::class, 'handle'])->name('payments.midtrans.webhook');

// Airports API Endpoints
Route::get('/api/airports', [AirportController::class, 'index'])->name('api.airports.index');
Route::get('/api/airports/search', [AirportController::class, 'index'])->name('api.airports.search');
Route::get('/api/airports/popular', [AirportController::class, 'popular'])->name('api.airports.popular');
Route::get('/api/airports/{iata}', [AirportController::class, 'show'])->name('api.airports.show');
Route::get('/api/flights/traffic', [AirportController::class, 'traffic'])->name('api.flights.traffic');

// AI Chatbot Endpoints
Route::get('/api/health', [ChatController::class, 'health'])->name('api.health');
Route::post('/api/chat', [ChatController::class, 'chat'])->middleware('throttle:20,1,chat')->name('api.chat');

// Legacy HTML redirects
Route::permanentRedirect('/index.html', '/');
Route::permanentRedirect('/packages.html', '/packages');
Route::permanentRedirect('/destinations.html', '/destinations');
Route::permanentRedirect('/about.html', '/about');
Route::permanentRedirect('/contact.html', '/contact');
Route::permanentRedirect('/bookings.html', '/bookings');
Route::permanentRedirect('/booking-review.html', '/booking-review');
Route::permanentRedirect('/booking-ready.html', '/booking-ready');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
    Route::post('/login', [AdminSessionController::class, 'store'])->middleware('throttle:admin-login')->name('login.store');
    Route::middleware(AdminAuthentication::class)->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('logout');
        Route::get('/{resource}', [AdminResourceController::class, 'index'])->name('resources.index');
        Route::get('/{resource}/create', [AdminResourceController::class, 'create'])->name('resources.create');
        Route::post('/{resource}', [AdminResourceController::class, 'store'])->name('resources.store');
        Route::get('/{resource}/{key}/edit', [AdminResourceController::class, 'edit'])->name('resources.edit');
        Route::get('/{resource}/{key}', [AdminResourceController::class, 'show'])->name('resources.show');
        Route::put('/{resource}/{key}', [AdminResourceController::class, 'update'])->name('resources.update');
        Route::delete('/{resource}/{key}', [AdminResourceController::class, 'destroy'])->name('resources.destroy');
    });
});
