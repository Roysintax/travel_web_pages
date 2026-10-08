<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Display Step 1: Trip planning & package selection.
     */
    public function index(): View
    {
        $page = Page::where('slug', 'bookings')->first();
        $packages = Package::with(['destination', 'image'])
            ->whereNotNull('booking_key')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $packagesData = $this->buildPackagesData($packages);

        return view('bookings', compact('page', 'packages', 'packagesData'));
    }

    /**
     * Display Step 2: Review booking details before final preview.
     */
    public function review(): View
    {
        $page = Page::where('slug', 'booking-review')->first();
        $packages = Package::with(['destination', 'image'])
            ->whereNotNull('booking_key')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $packagesData = $this->buildPackagesData($packages);

        return view('booking-review', compact('page', 'packagesData'));
    }

    /**
     * Display Step 3: Completed booking preview & payment selection.
     */
    public function ready(Request $request): View
    {
        $page = Page::where('slug', 'booking-ready')->first();
        $packages = Package::with(['destination', 'image'])
            ->whereNotNull('booking_key')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $packagesData = $this->buildPackagesData($packages);

        $sessionCodes = (array) $request->session()->get('booking_reference_codes', []);
        $latestBooking = null;
        if (! empty($sessionCodes)) {
            $latestBooking = Booking::whereIn('reference_code', $sessionCodes)
                ->with(['package', 'latestPayment'])
                ->latest()
                ->first();
        }

        return view('booking-ready', compact('page', 'packagesData', 'latestBooking'));
    }

    /**
     * Store booking draft in database.
     */
    public function store(StoreBookingRequest $request): JsonResponse|RedirectResponse
    {
        $selection = $request->validated('package');
        $package = Package::query()->active()
            ->where(function (Builder $query) use ($selection): void {
                $query->where('booking_key', $selection)->orWhere('slug', $selection);
                if (ctype_digit($selection)) {
                    $query->orWhere('id', $selection);
                }
            })
            ->first();

        if (! $package) {
            throw ValidationException::withMessages(['package' => 'Please select an available travel package.']);
        }

        $unitPrice = (float) $package->price_per_person;

        $bookingAttributes = [
            'reference_code' => Booking::generateReferenceCode(),
            'package_id' => $package->id,
            'departure_date' => $request->departure,
            'departure_city' => $request->origin,
            'travelers' => (int) $request->travelers,
            'lead_name' => $request->name,
            'lead_email' => $request->email,
            'notes' => $request->notes,
            'unit_price_snapshot' => $unitPrice,
            'currency' => $package->currency,
            'estimated_total' => $unitPrice * (int) $request->travelers,
            'preview_status' => 'draft',
        ];

        /** The imported MySQL schema computes this column; SQLite fixtures store it directly. */
        if ((new Booking)->getConnection()->getDriverName() === 'mysql') {
            unset($bookingAttributes['estimated_total']);
        }

        $booking = Booking::create($bookingAttributes)->refresh();

        $referenceCodes = $request->session()->get('booking_reference_codes', []);
        $referenceCodes[] = $booking->reference_code;
        $request->session()->put('booking_reference_codes', array_slice($referenceCodes, -50));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Trip draft created successfully.',
                'reference_code' => $booking->reference_code,
                'booking' => $booking,
            ], 201);
        }

        session(['latest_booking_code' => $booking->reference_code]);

        return redirect()->route('bookings.review');
    }

    /**
     * Update booking preview status (e.g. reviewed / ready).
     */
    public function updateStatus(Request $request, string $referenceCode): JsonResponse
    {
        abort_unless(in_array($referenceCode, $request->session()->get('booking_reference_codes', []), true), 404);

        $request->validate([
            'status' => ['required', 'string', 'in:draft,reviewed,ready'],
        ]);

        $booking = Booking::where('reference_code', $referenceCode)->firstOrFail();

        $updates = ['preview_status' => $request->status];
        if ($request->status === 'reviewed' && ! $booking->reviewed_at) {
            $updates['reviewed_at'] = now();
        }

        $booking->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Status updated.',
            'booking' => $booking,
        ]);
    }

    /**
     * Transform Eloquent packages into client-side JS format.
     *
     * @param  Collection<int, Package>  $packages
     * @return array<string, array{name: string, country: string, days: int, nights: int, price: int|float, image: string}>
     */
    private function buildPackagesData(Collection $packages): array
    {
        $defaultMap = [
            'greece' => [
                'name' => 'Greek Islands Escape',
                'country' => 'Greece',
                'days' => 6,
                'nights' => 5,
                'price' => 19500000,
                'image' => 'assets/greece.png',
            ],
            'maldives' => [
                'name' => 'Maldives Paradise',
                'country' => 'Maldives',
                'days' => 5,
                'nights' => 4,
                'price' => 24500000,
                'image' => 'assets/maldives.png',
            ],
            'canada' => [
                'name' => 'Canadian Rockies',
                'country' => 'Canada',
                'days' => 7,
                'nights' => 6,
                'price' => 27500000,
                'image' => 'assets/canada.png',
            ],
            'japan' => [
                'name' => 'Japan Discovery',
                'country' => 'Japan',
                'days' => 8,
                'nights' => 7,
                'price' => 33500000,
                'image' => 'assets/japan.png',
            ],
        ];

        if ($packages->isEmpty()) {
            return $defaultMap;
        }

        $result = [];
        foreach ($packages as $pkg) {
            $key = $pkg->booking_key ?: $pkg->slug;
            $country = $pkg->destination?->name ? (explode(',', $pkg->destination->name)[1] ?? $pkg->destination->name) : 'International';
            $country = trim($country);

            $result[$key] = [
                'name' => $pkg->title,
                'country' => $country ?: ($defaultMap[$key]['country'] ?? 'World'),
                'days' => (int) $pkg->days,
                'nights' => (int) $pkg->nights,
                'price' => (float) $pkg->price_per_person,
                'image' => $pkg->image?->file_path ?: "assets/{$key}.png",
            ];
        }

        // Merge defaults to ensure greece, maldives, canada, japan keys exist for client flow
        return array_merge($defaultMap, $result);
    }
}
