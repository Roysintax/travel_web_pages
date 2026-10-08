<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AirLabs\AirportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function __construct(
        protected AirportService $airportService
    ) {}

    /**
     * Search airports by query string or list popular airports if query is empty.
     */
    public function index(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $limit = max(1, min((int) $request->query('limit', 12), 30));

        $airports = $this->airportService->search($query, $limit);

        return response()->json([
            'success' => true,
            'data' => $airports,
        ]);
    }

    /**
     * Retrieve curated popular departure airports for default dropdown view.
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('limit', 8), 20));
        $airports = $this->airportService->getPopularAirports($limit);

        return response()->json([
            'success' => true,
            'data' => $airports,
        ]);
    }

    /**
     * Retrieve details for a single airport by IATA code.
     */
    public function show(string $iata): JsonResponse
    {
        $airport = $this->airportService->getAirportByIata($iata);

        if (! $airport) {
            return response()->json([
                'success' => false,
                'message' => "Airport with IATA code '{$iata}' not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $airport,
        ]);
    }

    /**
     * Retrieve active flight traffic corridor data for globe & radar simulation.
     */
    public function traffic(Request $request): JsonResponse
    {
        $traffic = $this->airportService->getTrafficFlights();

        return response()->json([
            'success' => true,
            'count' => count($traffic),
            'data' => $traffic,
        ]);
    }
}
