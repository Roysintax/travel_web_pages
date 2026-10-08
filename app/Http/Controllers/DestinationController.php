<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Package;
use App\Models\Page;
use Illuminate\View\View;

class DestinationController extends Controller
{
    public function index(): View
    {
        $destinations = Destination::query()
            ->with(['image', 'primaryPackage'])
            ->get();

        $topDeals = Package::query()
            ->active()
            ->whereIn('booking_key', ['greece', 'maldives', 'japan'])
            ->with(['destination', 'image'])
            ->get()
            ->sortBy(fn (Package $p) => array_search($p->booking_key, ['greece', 'maldives', 'japan']))
            ->values();

        $page = Page::query()->where('slug', 'destinations')->first();

        $regions = $destinations->pluck('region')->unique()->values();

        return view('destinations', [
            'destinations' => $destinations,
            'topDeals' => $topDeals,
            'page' => $page,
            'regions' => $regions,
        ]);
    }
}
