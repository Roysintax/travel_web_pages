<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Booking keys featured on Home, in the original HTML order. */
    private const FEATURED = ['greece', 'maldives', 'canada', 'japan'];

    public function __invoke(): View
    {
        $packages = Package::query()
            ->active()
            ->whereIn('booking_key', self::FEATURED)
            ->with(['destination', 'image'])
            ->get()
            ->sortBy(fn (Package $p) => array_search($p->booking_key, self::FEATURED))
            ->values();

        return view('home', compact('packages'));
    }
}
