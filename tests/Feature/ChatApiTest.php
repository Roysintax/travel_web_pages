<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_health_endpoint_returns_json(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'chatConfigured',
        ]);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_chat_api_validates_message_presence(): void
    {
        $response = $this->postJson('/api/chat', []);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'INVALID_INPUT',
            ],
        ]);
    }

    public function test_chat_api_validates_maximum_message_length(): void
    {
        $response = $this->postJson('/api/chat', [
            'message' => str_repeat('a', 5001),
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'INVALID_INPUT',
            ],
        ]);
    }

    public function test_chat_api_returns_ai_reply_with_mocked_groq_service(): void
    {
        Http::fake([
            'https://api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Halo! Paket ke Yunani mulai dari Rp 19.500.000 per orang untuk 6 hari 5 malam.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        config(['services.groq.api_key' => 'fake-test-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Berapa harga paket ke Yunani?',
            'history' => [
                ['role' => 'user', 'content' => 'Halo'],
                ['role' => 'assistant', 'content' => 'Halo, ada yang bisa saya bantu?'],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Halo! Paket ke Yunani mulai dari Rp 19.500.000 per orang untuk 6 hari 5 malam.',
            'data' => [
                'message' => 'Halo! Paket ke Yunani mulai dari Rp 19.500.000 per orang untuk 6 hari 5 malam.',
            ],
        ]);
    }

    public function test_chat_api_falls_back_gracefully_when_groq_is_unreachable(): void
    {
        Http::fake([
            'https://api.groq.com/*' => Http::response('Server error', 500),
        ]);

        config(['services.groq.api_key' => 'fake-test-key']);

        $response = $this->postJson('/api/chat', [
            'message' => 'Apa saja paket yang tersedia?',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['message'],
        ]);
        $response->assertJson([
            'success' => true,
        ]);
        // The fallback contains package information
        $this->assertStringContainsString('Greece Tour', $response->json('message'));
    }
}
