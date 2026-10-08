<?php

namespace App\Services\AirLabs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AirportService
{
    /**
     * Cache key for cached airport list.
     */
    public const CACHE_KEY = 'airlabs.airports.v1';

    /**
     * Cache duration (7 days).
     */
    public const CACHE_TTL_DAYS = 7;

    /**
     * Search airports by query string (IATA, city, or airport name).
     *
     * @return array<int, array{iata: string, icao: ?string, name: string, city: ?string, country: ?string, timezone: ?string, lat: ?float, lng: ?float}>
     */
    public function search(string $query, int $limit = 12): array
    {
        $cleanQuery = trim($query);

        if ($cleanQuery === '') {
            return $this->getPopularAirports($limit);
        }

        $allAirports = $this->getAllAirports();
        $q = mb_strtolower($cleanQuery);
        $len = mb_strlen($q);

        $exactIata = [];
        $prefixIata = [];
        $exactCity = [];
        $prefixCity = [];
        $containsCity = [];
        $prefixName = [];
        $containsName = [];
        $otherMatches = [];

        foreach ($allAirports as $airport) {
            $iata = mb_strtolower($airport['iata'] ?? '');
            $city = mb_strtolower($airport['city'] ?? '');
            $name = mb_strtolower($airport['name'] ?? '');
            $country = mb_strtolower($airport['country'] ?? '');

            if ($iata === $q) {
                $exactIata[] = $airport;
            } elseif (str_starts_with($iata, $q)) {
                $prefixIata[] = $airport;
            } elseif ($city === $q) {
                $exactCity[] = $airport;
            } elseif (str_starts_with($city, $q)) {
                $prefixCity[] = $airport;
            } elseif (str_contains($city, $q)) {
                $containsCity[] = $airport;
            } elseif (str_starts_with($name, $q)) {
                $prefixName[] = $airport;
            } elseif (str_contains($name, $q)) {
                $containsName[] = $airport;
            } elseif ($country === $q || str_contains($country, $q)) {
                $otherMatches[] = $airport;
            }
        }

        $merged = array_merge(
            $exactIata,
            $prefixIata,
            $exactCity,
            $prefixCity,
            $containsCity,
            $prefixName,
            $containsName,
            $otherMatches
        );

        // Deduplicate by IATA
        $seen = [];
        $results = [];
        foreach ($merged as $item) {
            $code = $item['iata'];
            if (! isset($seen[$code])) {
                $seen[$code] = true;
                $results[] = $item;
                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Get a curated list of top popular departure airports.
     *
     * @return array<int, array{iata: string, icao: ?string, name: string, city: ?string, country: ?string, timezone: ?string, lat: ?float, lng: ?float}>
     */
    public function getPopularAirports(int $limit = 8): array
    {
        $priorityCodes = [
            'CGK', // Jakarta Soekarno-Hatta
            'DPS', // Bali Ngurah Rai
            'SUB', // Surabaya Juanda
            'KNO', // Medan Kualanamu
            'JOG', // Yogyakarta Adisutjipto / YIA
            'YIA', // Yogyakarta International
            'HLP', // Jakarta Halim
            'UPG', // Makassar Sultan Hasanuddin
            'BPN', // Balikpapan Sepinggan
            'SRG', // Semarang Ahmad Yani
            'BDJ', // Banjarmasin Syamsudin Noor
            'PLM', // Palembang Sultan Mahmud Badaruddin II
            'SIN', // Singapore Changi
            'KUL', // Kuala Lumpur
            'HND', // Tokyo Haneda
            'NRT', // Tokyo Narita
            'ICN', // Seoul Incheon
            'DXB', // Dubai
            'JED', // Jeddah King Abdulaziz
            'LHR', // London Heathrow
        ];

        $allAirports = $this->getAllAirports();
        $map = [];
        foreach ($allAirports as $item) {
            $map[$item['iata']] = $item;
        }

        $popular = [];
        foreach ($priorityCodes as $code) {
            if (isset($map[$code])) {
                $popular[] = $map[$code];
                if (count($popular) >= $limit) {
                    break;
                }
            }
        }

        if (empty($popular)) {
            $popular = array_slice($allAirports, 0, $limit);
        }

        return $popular;
    }

    /**
     * Find a single airport by its 3-letter IATA code.
     *
     * @return array{iata: string, icao: ?string, name: string, city: ?string, country: ?string, timezone: ?string, lat: ?float, lng: ?float}|null
     */
    public function getAirportByIata(string $iata): ?array
    {
        $code = strtoupper(trim($iata));
        $all = $this->getAllAirports();

        foreach ($all as $airport) {
            if ($airport['iata'] === $code) {
                return $airport;
            }
        }

        return null;
    }

    /**
     * Get active flight traffic around major flight corridors for simulation/radar.
     *
     * @return array<int, array{flight_iata: string, airline: string, origin: string, destination: string, origin_coords: array{lat: float, lng: float}, dest_coords: array{lat: float, lng: float}, altitude: int, speed: int, status: string, progress: float}>
     */
    public function getTrafficFlights(): array
    {
        $trafficPresets = [
            [
                'flight_iata' => 'GA-882',
                'airline' => 'Garuda Indonesia',
                'origin' => 'CGK',
                'destination' => 'NRT',
                'altitude' => 11200,
                'speed' => 885,
                'status' => 'En Route',
                'progress' => 0.42,
            ],
            [
                'flight_iata' => 'SQ-951',
                'airline' => 'Singapore Airlines',
                'origin' => 'CGK',
                'destination' => 'SIN',
                'altitude' => 8500,
                'speed' => 740,
                'status' => 'En Route',
                'progress' => 0.68,
            ],
            [
                'flight_iata' => 'EK-358',
                'airline' => 'Emirates',
                'origin' => 'DXB',
                'destination' => 'CGK',
                'altitude' => 11800,
                'speed' => 910,
                'status' => 'En Route',
                'progress' => 0.73,
            ],
            [
                'flight_iata' => 'JL-726',
                'airline' => 'Japan Airlines',
                'origin' => 'NRT',
                'destination' => 'CGK',
                'altitude' => 10900,
                'speed' => 870,
                'status' => 'En Route',
                'progress' => 0.28,
            ],
            [
                'flight_iata' => 'QF-41',
                'airline' => 'Qantas',
                'origin' => 'SYD',
                'destination' => 'DPS',
                'altitude' => 11500,
                'speed' => 860,
                'status' => 'En Route',
                'progress' => 0.55,
            ],
            [
                'flight_iata' => 'BA-11',
                'airline' => 'British Airways',
                'origin' => 'LHR',
                'destination' => 'SIN',
                'altitude' => 11900,
                'speed' => 920,
                'status' => 'En Route',
                'progress' => 0.81,
            ],
            [
                'flight_iata' => 'SV-816',
                'airline' => 'Saudia',
                'origin' => 'JED',
                'destination' => 'SUB',
                'altitude' => 11400,
                'speed' => 895,
                'status' => 'En Route',
                'progress' => 0.35,
            ],
            [
                'flight_iata' => 'MH-721',
                'airline' => 'Malaysia Airlines',
                'origin' => 'KUL',
                'destination' => 'CGK',
                'altitude' => 7800,
                'speed' => 720,
                'status' => 'Approaching',
                'progress' => 0.88,
            ],
        ];

        $results = [];
        foreach ($trafficPresets as $flight) {
            $origin = $this->getAirportByIata($flight['origin']);
            $dest = $this->getAirportByIata($flight['destination']);

            if ($origin && $dest && $origin['lat'] !== null && $origin['lng'] !== null && $dest['lat'] !== null && $dest['lng'] !== null) {
                $results[] = [
                    'flight_iata' => $flight['flight_iata'],
                    'airline' => $flight['airline'],
                    'origin' => $flight['origin'],
                    'origin_name' => $origin['name'],
                    'origin_city' => $origin['city'],
                    'destination' => $flight['destination'],
                    'dest_name' => $dest['name'],
                    'dest_city' => $dest['city'],
                    'origin_coords' => ['lat' => $origin['lat'], 'lng' => $origin['lng']],
                    'dest_coords' => ['lat' => $dest['lat'], 'lng' => $dest['lng']],
                    'altitude' => $flight['altitude'],
                    'speed' => $flight['speed'],
                    'status' => $flight['status'],
                    'progress' => $flight['progress'],
                ];
            }
        }

        return $results;
    }

    /**
     * Retrieve all airports, using cache and falling back gracefully.
     *
     * @return array<int, array{iata: string, icao: ?string, name: string, city: ?string, country: ?string, timezone: ?string, lat: ?float, lng: ?float}>
     */
    public function getAllAirports(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addDays(self::CACHE_TTL_DAYS), function () {
            $apiKey = config('services.airlabs.key');
            $baseUrl = rtrim((string) config('services.airlabs.base_url', 'https://airlabs.co/api/v9'), '/');

            if (! empty($apiKey)) {
                try {
                    $response = Http::timeout(10)
                        ->retry(2, 300)
                        ->get($baseUrl.'/airports', [
                            'api_key' => $apiKey,
                            '_fields' => 'name,iata_code,icao_code,city,country_code,timezone,lat,lng,is_international',
                        ]);

                    if ($response->successful()) {
                        $json = $response->json();
                        $items = $json['response'] ?? $json['data'] ?? $json;

                        if (is_array($items) && ! empty($items)) {
                            $normalized = $this->normalizeAirports($items);
                            if (! empty($normalized)) {
                                return $this->mergeWithCurated($normalized);
                            }
                        }
                    } else {
                        Log::warning('AirLabs airports API request failed', [
                            'status' => $response->status(),
                            'endpoint' => '/airports',
                        ]);
                    }
                } catch (Throwable $e) {
                    Log::error('AirLabs request exception', [
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            // Fallback to rich curated default dataset
            return $this->getCuratedFallbackAirports();
        });
    }

    /**
     * Normalize raw AirLabs airport list into internal schema.
     *
     * @param  array<int, mixed>  $rawAirports
     * @return array<int, array{iata: string, icao: ?string, name: string, city: ?string, country: ?string, timezone: ?string, lat: ?float, lng: ?float}>
     */
    public function normalizeAirports(array $rawAirports): array
    {
        $normalized = [];

        foreach ($rawAirports as $item) {
            if (! is_array($item)) {
                continue;
            }

            $iata = strtoupper(trim((string) ($item['iata_code'] ?? $item['iata'] ?? '')));
            $name = trim((string) ($item['name'] ?? ''));

            // Must have valid 3-letter IATA code and name
            if (strlen($iata) !== 3 || $name === '') {
                continue;
            }

            $normalized[] = [
                'iata' => $iata,
                'icao' => ! empty($item['icao_code'] ?? $item['icao'] ?? null) ? strtoupper(trim((string) ($item['icao_code'] ?? $item['icao']))) : null,
                'name' => $name,
                'city' => ! empty($item['city']) ? trim((string) $item['city']) : null,
                'country' => ! empty($item['country_code'] ?? $item['country'] ?? null) ? strtoupper(trim((string) ($item['country_code'] ?? $item['country']))) : null,
                'timezone' => ! empty($item['timezone']) ? trim((string) $item['timezone']) : null,
                'lat' => isset($item['lat']) ? (float) $item['lat'] : null,
                'lng' => isset($item['lng']) ? (float) $item['lng'] : null,
            ];
        }

        return $normalized;
    }

    /**
     * Merge fetched list with curated dataset to guarantee major hubs are present.
     *
     * @param  array<int, array{iata: string, icao: ?string, name: string, city: ?string, country: ?string, timezone: ?string, lat: ?float, lng: ?float}>  $fetched
     * @return array<int, array{iata: string, icao: ?string, name: string, city: ?string, country: ?string, timezone: ?string, lat: ?float, lng: ?float}>
     */
    protected function mergeWithCurated(array $fetched): array
    {
        $map = [];
        foreach ($fetched as $item) {
            $map[$item['iata']] = $item;
        }

        foreach ($this->getCuratedFallbackAirports() as $curated) {
            if (! isset($map[$curated['iata']])) {
                $map[$curated['iata']] = $curated;
            }
        }

        return array_values($map);
    }

    /**
     * Curated dataset of major Indonesian and global airports as reliable fallback.
     *
     * @return array<int, array{iata: string, icao: ?string, name: string, city: ?string, country: ?string, timezone: ?string, lat: ?float, lng: ?float}>
     */
    public function getCuratedFallbackAirports(): array
    {
        return [
            // Indonesia - Major & Regional Hubs
            [
                'iata' => 'CGK',
                'icao' => 'WIII',
                'name' => 'Soekarno-Hatta International Airport',
                'city' => 'Jakarta',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -6.125567,
                'lng' => 106.655897,
            ],
            [
                'iata' => 'HLP',
                'icao' => 'WIHH',
                'name' => 'Halim Perdanakusuma International Airport',
                'city' => 'Jakarta',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -6.266667,
                'lng' => 106.890833,
            ],
            [
                'iata' => 'DPS',
                'icao' => 'WADD',
                'name' => 'I Gusti Ngurah Rai International Airport',
                'city' => 'Denpasar (Bali)',
                'country' => 'ID',
                'timezone' => 'Asia/Makassar',
                'lat' => -8.748167,
                'lng' => 115.167167,
            ],
            [
                'iata' => 'SUB',
                'icao' => 'WARR',
                'name' => 'Juanda International Airport',
                'city' => 'Surabaya',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -7.379833,
                'lng' => 112.7875,
            ],
            [
                'iata' => 'KNO',
                'icao' => 'WIMM',
                'name' => 'Kualanamu International Airport',
                'city' => 'Medan',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => 3.642222,
                'lng' => 98.885278,
            ],
            [
                'iata' => 'YIA',
                'icao' => 'WAHI',
                'name' => 'Yogyakarta International Airport',
                'city' => 'Kulon Progo (Yogyakarta)',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -7.900833,
                'lng' => 110.054444,
            ],
            [
                'iata' => 'JOG',
                'icao' => 'WARJ',
                'name' => 'Adisutjipto International Airport',
                'city' => 'Yogyakarta',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -7.788056,
                'lng' => 110.431667,
            ],
            [
                'iata' => 'UPG',
                'icao' => 'WAAA',
                'name' => 'Sultan Hasanuddin International Airport',
                'city' => 'Makassar',
                'country' => 'ID',
                'timezone' => 'Asia/Makassar',
                'lat' => -5.061667,
                'lng' => 119.554167,
            ],
            [
                'iata' => 'BPN',
                'icao' => 'WALL',
                'name' => 'Sultan Aji Muhammad Sulaiman Sepinggan Airport',
                'city' => 'Balikpapan',
                'country' => 'ID',
                'timezone' => 'Asia/Makassar',
                'lat' => -1.268333,
                'lng' => 116.894444,
            ],
            [
                'iata' => 'SRG',
                'icao' => 'WAHS',
                'name' => 'Jenderal Ahmad Yani International Airport',
                'city' => 'Semarang',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -6.974167,
                'lng' => 110.374444,
            ],
            [
                'iata' => 'SOC',
                'icao' => 'WARQ',
                'name' => 'Adisumarmo International Airport',
                'city' => 'Solo (Surakarta)',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -7.516111,
                'lng' => 110.756944,
            ],
            [
                'iata' => 'BDO',
                'icao' => 'WICC',
                'name' => 'Husein Sastranegara International Airport',
                'city' => 'Bandung',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -6.900556,
                'lng' => 107.576111,
            ],
            [
                'iata' => 'KJT',
                'icao' => 'WICA',
                'name' => 'Kertajati International Airport',
                'city' => 'Majalengka (Bandung)',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -6.654722,
                'lng' => 108.231944,
            ],
            [
                'iata' => 'PLM',
                'icao' => 'WIPP',
                'name' => 'Sultan Mahmud Badaruddin II International Airport',
                'city' => 'Palembang',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -2.898333,
                'lng' => 104.700278,
            ],
            [
                'iata' => 'PKU',
                'icao' => 'WIBB',
                'name' => 'Sultan Syarif Kasim II International Airport',
                'city' => 'Pekanbaru',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => 0.460833,
                'lng' => 101.445,
            ],
            [
                'iata' => 'PDG',
                'icao' => 'WIEE',
                'name' => 'Minangkabau International Airport',
                'city' => 'Padang',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -0.786667,
                'lng' => 100.280556,
            ],
            [
                'iata' => 'BDJ',
                'icao' => 'WAOO',
                'name' => 'Syamsudin Noor International Airport',
                'city' => 'Banjarmasin',
                'country' => 'ID',
                'timezone' => 'Asia/Makassar',
                'lat' => -3.442222,
                'lng' => 114.757778,
            ],
            [
                'iata' => 'LOP',
                'icao' => 'WADL',
                'name' => 'Lombok International Airport (Zainuddin Abdul Madjid)',
                'city' => 'Praya (Lombok)',
                'country' => 'ID',
                'timezone' => 'Asia/Makassar',
                'lat' => -8.758889,
                'lng' => 116.276667,
            ],
            [
                'iata' => 'LBJ',
                'icao' => 'WATO',
                'name' => 'Komodo International Airport',
                'city' => 'Labuan Bajo',
                'country' => 'ID',
                'timezone' => 'Asia/Makassar',
                'lat' => -8.4875,
                'lng' => 119.888889,
            ],
            [
                'iata' => 'MDC',
                'icao' => 'WAMM',
                'name' => 'Sam Ratulangi International Airport',
                'city' => 'Manado',
                'country' => 'ID',
                'timezone' => 'Asia/Makassar',
                'lat' => 1.549167,
                'lng' => 124.926111,
            ],
            [
                'iata' => 'KOE',
                'icao' => 'WATT',
                'name' => 'El Tari International Airport',
                'city' => 'Kupang',
                'country' => 'ID',
                'timezone' => 'Asia/Makassar',
                'lat' => -10.171389,
                'lng' => 123.670833,
            ],
            [
                'iata' => 'PNK',
                'icao' => 'WIOO',
                'name' => 'Supadio International Airport',
                'city' => 'Pontianak',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => -0.150556,
                'lng' => 109.403889,
            ],
            [
                'iata' => 'BTJ',
                'icao' => 'WITT',
                'name' => 'Sultan Iskandar Muda International Airport',
                'city' => 'Banda Aceh',
                'country' => 'ID',
                'timezone' => 'Asia/Jakarta',
                'lat' => 5.523611,
                'lng' => 95.417778,
            ],
            [
                'iata' => 'DJJ',
                'icao' => 'WAJJ',
                'name' => 'Sentani International Airport',
                'city' => 'Jayapura',
                'country' => 'ID',
                'timezone' => 'Asia/Jayapura',
                'lat' => -2.576944,
                'lng' => 140.516111,
            ],

            // Southeast Asia & Asia Pacific
            [
                'iata' => 'SIN',
                'icao' => 'WSSS',
                'name' => 'Singapore Changi Airport',
                'city' => 'Singapore',
                'country' => 'SG',
                'timezone' => 'Asia/Singapore',
                'lat' => 1.350189,
                'lng' => 103.994433,
            ],
            [
                'iata' => 'KUL',
                'icao' => 'WMKK',
                'name' => 'Kuala Lumpur International Airport',
                'city' => 'Kuala Lumpur',
                'country' => 'MY',
                'timezone' => 'Asia/Kuala_Lumpur',
                'lat' => 2.745578,
                'lng' => 101.709917,
            ],
            [
                'iata' => 'BKK',
                'icao' => 'VTBS',
                'name' => 'Suvarnabhumi Airport',
                'city' => 'Bangkok',
                'country' => 'TH',
                'timezone' => 'Asia/Bangkok',
                'lat' => 13.681111,
                'lng' => 100.747222,
            ],
            [
                'iata' => 'DMK',
                'icao' => 'VTBD',
                'name' => 'Don Mueang International Airport',
                'city' => 'Bangkok',
                'country' => 'TH',
                'timezone' => 'Asia/Bangkok',
                'lat' => 13.9125,
                'lng' => 100.606667,
            ],
            [
                'iata' => 'HND',
                'icao' => 'RJTT',
                'name' => 'Tokyo Haneda International Airport',
                'city' => 'Tokyo',
                'country' => 'JP',
                'timezone' => 'Asia/Tokyo',
                'lat' => 35.552258,
                'lng' => 139.779694,
            ],
            [
                'iata' => 'NRT',
                'icao' => 'RJAA',
                'name' => 'Narita International Airport',
                'city' => 'Tokyo',
                'country' => 'JP',
                'timezone' => 'Asia/Tokyo',
                'lat' => 35.764722,
                'lng' => 140.386389,
            ],
            [
                'iata' => 'KIX',
                'icao' => 'RJBB',
                'name' => 'Kansai International Airport',
                'city' => 'Osaka',
                'country' => 'JP',
                'timezone' => 'Asia/Tokyo',
                'lat' => 34.427222,
                'lng' => 135.244167,
            ],
            [
                'iata' => 'ICN',
                'icao' => 'RKSI',
                'name' => 'Incheon International Airport',
                'city' => 'Seoul',
                'country' => 'KR',
                'timezone' => 'Asia/Seoul',
                'lat' => 37.469167,
                'lng' => 126.450556,
            ],
            [
                'iata' => 'HKG',
                'icao' => 'VHHH',
                'name' => 'Hong Kong International Airport',
                'city' => 'Hong Kong',
                'country' => 'HK',
                'timezone' => 'Asia/Hong_Kong',
                'lat' => 22.308889,
                'lng' => 113.914444,
            ],
            [
                'iata' => 'TPE',
                'icao' => 'RCTP',
                'name' => 'Taiwan Taoyuan International Airport',
                'city' => 'Taipei',
                'country' => 'TW',
                'timezone' => 'Asia/Taipei',
                'lat' => 25.077778,
                'lng' => 121.232778,
            ],
            [
                'iata' => 'SYD',
                'icao' => 'YSSY',
                'name' => 'Sydney Kingsford Smith Airport',
                'city' => 'Sydney',
                'country' => 'AU',
                'timezone' => 'Australia/Sydney',
                'lat' => -33.946111,
                'lng' => 151.177222,
            ],
            [
                'iata' => 'MEL',
                'icao' => 'YMML',
                'name' => 'Melbourne Airport (Tullamarine)',
                'city' => 'Melbourne',
                'country' => 'AU',
                'timezone' => 'Australia/Melbourne',
                'lat' => -37.673333,
                'lng' => 144.843333,
            ],

            // Middle East
            [
                'iata' => 'JED',
                'icao' => 'OEJN',
                'name' => 'King Abdulaziz International Airport',
                'city' => 'Jeddah',
                'country' => 'SA',
                'timezone' => 'Asia/Riyadh',
                'lat' => 21.679564,
                'lng' => 39.156536,
            ],
            [
                'iata' => 'MED',
                'icao' => 'OEMA',
                'name' => 'Prince Mohammad bin Abdulaziz International Airport',
                'city' => 'Medina',
                'country' => 'SA',
                'timezone' => 'Asia/Riyadh',
                'lat' => 24.553333,
                'lng' => 39.705,
            ],
            [
                'iata' => 'DXB',
                'icao' => 'OMDB',
                'name' => 'Dubai International Airport',
                'city' => 'Dubai',
                'country' => 'AE',
                'timezone' => 'Asia/Dubai',
                'lat' => 25.252778,
                'lng' => 55.364444,
            ],
            [
                'iata' => 'DOH',
                'icao' => 'OTHH',
                'name' => 'Hamad International Airport',
                'city' => 'Doha',
                'country' => 'QA',
                'timezone' => 'Asia/Qatar',
                'lat' => 25.273056,
                'lng' => 51.608056,
            ],
            [
                'iata' => 'IST',
                'icao' => 'LTFM',
                'name' => 'Istanbul Airport',
                'city' => 'Istanbul',
                'country' => 'TR',
                'timezone' => 'Europe/Istanbul',
                'lat' => 41.275278,
                'lng' => 28.751944,
            ],

            // Europe & Americas
            [
                'iata' => 'LHR',
                'icao' => 'EGLL',
                'name' => 'Heathrow Airport',
                'city' => 'London',
                'country' => 'GB',
                'timezone' => 'Europe/London',
                'lat' => 51.4775,
                'lng' => -0.461389,
            ],
            [
                'iata' => 'CDG',
                'icao' => 'LFPG',
                'name' => 'Charles de Gaulle Airport',
                'city' => 'Paris',
                'country' => 'FR',
                'timezone' => 'Europe/Paris',
                'lat' => 49.009722,
                'lng' => 2.547778,
            ],
            [
                'iata' => 'AMS',
                'icao' => 'EHAM',
                'name' => 'Amsterdam Airport Schiphol',
                'city' => 'Amsterdam',
                'country' => 'NL',
                'timezone' => 'Europe/Amsterdam',
                'lat' => 52.308611,
                'lng' => 4.763889,
            ],
            [
                'iata' => 'FRA',
                'icao' => 'EDDF',
                'name' => 'Frankfurt Airport',
                'city' => 'Frankfurt',
                'country' => 'DE',
                'timezone' => 'Europe/Berlin',
                'lat' => 50.026422,
                'lng' => 8.543125,
            ],
            [
                'iata' => 'ATH',
                'icao' => 'LGAV',
                'name' => 'Athens International Airport (Eleftherios Venizelos)',
                'city' => 'Athens',
                'country' => 'GR',
                'timezone' => 'Europe/Athens',
                'lat' => 37.936389,
                'lng' => 23.944444,
            ],
            [
                'iata' => 'MLE',
                'icao' => 'VRMM',
                'name' => 'Velana International Airport',
                'city' => 'Male (Maldives)',
                'country' => 'MV',
                'timezone' => 'Indian/Maldives',
                'lat' => 4.191833,
                'lng' => 73.529056,
            ],
            [
                'iata' => 'YYC',
                'icao' => 'CYYC',
                'name' => 'Calgary International Airport',
                'city' => 'Calgary',
                'country' => 'CA',
                'timezone' => 'America/Edmonton',
                'lat' => 51.113889,
                'lng' => -114.020278,
            ],
            [
                'iata' => 'JFK',
                'icao' => 'KJFK',
                'name' => 'John F. Kennedy International Airport',
                'city' => 'New York',
                'country' => 'US',
                'timezone' => 'America/New_York',
                'lat' => 40.639751,
                'lng' => -73.778926,
            ],
            [
                'iata' => 'LAX',
                'icao' => 'KLAX',
                'name' => 'Los Angeles International Airport',
                'city' => 'Los Angeles',
                'country' => 'US',
                'timezone' => 'America/Los_Angeles',
                'lat' => 33.942496,
                'lng' => -118.408049,
            ],
        ];
    }
}
