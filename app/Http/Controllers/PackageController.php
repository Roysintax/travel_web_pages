<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Package;
use App\Models\Page;
use App\Models\PricingPlan;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(): View
    {
        $packages = Package::query()
            ->active()
            ->with([
                'destination',
                'image',
                'features',
                'included',
                'itineraries',
                'accommodation',
            ])
            ->get();

        $packagesJson = $packages->map(fn (Package $p) => $p->toCatalogArray())->values()->all();

        $pricingPlans = PricingPlan::query()->get();

        $page = Page::query()->where('slug', 'packages')->first();

        $faqs = $page
            ? $page->faqs
            : Faq::query()->where('page_id', 8)->orderBy('sort_order')->get();

        return view('packages', [
            'packages' => $packages,
            'packagesJson' => $packagesJson,
            'pricingPlans' => $pricingPlans,
            'faqs' => $faqs,
            'page' => $page,
        ]);
    }
}
