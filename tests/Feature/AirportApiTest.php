<?php

namespace Tests\Feature;

use App\Services\AirLabs\AirportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AirportApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_can_retrieve_default_airport_list(): void
    {
        $response = $this->getJson('/api/airports');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'iata',
                        'name',
                        'city',
                        'country',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals(3, strlen($data[0]['iata']));
    }

    public function test_can_search_airports_by_iata_code(): void
    {
        $response = $this->getJson('/api/airports/search?q=CGK');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('CGK', $data[0]['iata']);
        $this->assertStringContainsString('Soekarno-Hatta', $data[0]['name']);
    }

    public function test_can_search_airports_by_city_name(): void
    {
        $response = $this->getJson('/api/airports/search?q=bali');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $iatas = array_column($data, 'iata');
        $this->assertContains('DPS', $iatas);
    }

    public function test_can_retrieve_popular_departure_airports(): void
    {
        $response = $this->getJson('/api/airports/popular?limit=5');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(5, $data);
        $this->assertEquals('CGK', $data[0]['iata']);
    }

    public function test_api_never_exposes_secret_airlabs_api_key(): void
    {
        Config::set('services.airlabs.key', 'SUPER_SECRET_AIRLABS_KEY_12345');

        $response = $this->getJson('/api/airports/search?q=CGK');
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('SUPER_SECRET_AIRLABS_KEY_12345', $content);
        $this->assertStringNotContainsString('api_key', $content);
    }

    public function test_airlabs_http_response_is_normalized_and_cached(): void
    {
        Config::set('services.airlabs.key', 'test_key');
        Config::set('services.airlabs.base_url', 'https://airlabs.co/api/v9');

        Http::fake([
            'https://airlabs.co/api/v9/airports*' => Http::response([
                'response' => [
                    [
                        'name' => 'Test International Airport',
                        'iata_code' => 'TST',
                        'icao_code' => 'WTST',
                        'city' => 'Testville',
                        'country_code' => 'ID',
                        'timezone' => 'Asia/Jakarta',
                        'lat' => -6.1,
                        'lng' => 106.8,
                    ],
                    [
                        // Invalid IATA (missing / not 3 letters) -> should be filtered out
                        'name' => 'Broken Airport',
                        'iata_code' => 'INVALID',
                        'city' => 'Nowhere',
                    ],
                ],
            ], 200),
        ]);

        $service = app(AirportService::class);
        $airports = $service->getAllAirports();

        // Verify normalized airport
        $testAirport = collect($airports)->firstWhere('iata', 'TST');
        $this->assertNotNull($testAirport);
        $this->assertEquals('Test International Airport', $testAirport['name']);
        $this->assertEquals('Testville', $testAirport['city']);

        // Verify invalid airport is filtered out
        $invalidAirport = collect($airports)->firstWhere('iata', 'INVALID');
        $this->assertNull($invalidAirport);

        // Verify cache hit: subsequent request should not trigger HTTP call
        Http::assertSentCount(1);
        $service->getAllAirports();
        Http::assertSentCount(1);
    }

    public function test_falls_back_gracefully_when_airlabs_fails_or_times_out(): void
    {
        Config::set('services.airlabs.key', 'test_key');
        Config::set('services.airlabs.base_url', 'https://airlabs.co/api/v9');

        Http::fake([
            'https://airlabs.co/api/v9/airports*' => Http::response(null, 500),
        ]);

        $service = app(AirportService::class);
        $airports = $service->getAllAirports();

        $this->assertNotEmpty($airports);
        $this->assertNotNull(collect($airports)->firstWhere('iata', 'CGK'));
    }

    public function test_can_retrieve_single_airport_by_iata(): void
    {
        $response = $this->getJson('/api/airports/CGK');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.iata', 'CGK')
            ->assertJsonPath('data.city', 'Jakarta');

        $notFound = $this->getJson('/api/airports/UNKNOWN999');
        $notFound->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_can_retrieve_traffic_flights(): void
    {
        $response = $this->getJson('/api/flights/traffic');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'count',
                'data' => [
                    '*' => [
                        'flight_iata',
                        'airline',
                        'origin',
                        'destination',
                        'origin_coords',
                        'dest_coords',
                    ],
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('count'));
    }
}
