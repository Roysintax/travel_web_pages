<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Destination;
use App\Models\Package;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', ['counts' => ['Paket aktif' => Package::where('is_active', true)->count(), 'Destinasi' => Destination::count(), 'Booking preview' => Booking::count(), 'Pesan baru' => ContactMessage::where('status', 'new')->count()], 'bookings' => Booking::latest()->limit(5)->get(), 'contacts' => ContactMessage::orderByDesc('created_at')->limit(4)->get()]);
    }
}
