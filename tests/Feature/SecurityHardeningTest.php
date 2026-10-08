<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_include_security_headers(): void
    {
        $this->getJson('/api/health')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_chat_requires_csrf_token_outside_test_environment(): void
    {
        config(['services.groq.api_key' => null]);
        $this->app->instance('env', 'local');
        $this->postJson('/api/chat', ['message' => 'Halo'])->assertStatus(419);
    }

    public function test_chat_accepts_matching_csrf_token(): void
    {
        config(['services.groq.api_key' => null]);
        $this->app->instance('env', 'local');
        $this->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->postJson('/api/chat', ['message' => 'Halo'])->assertOk();
    }

    public function test_chat_is_rate_limited_before_calling_provider(): void
    {
        config(['services.groq.api_key' => null]);
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->postJson('/api/chat', ['message' => 'Halo'])->assertOk();
        }
        $this->postJson('/api/chat', ['message' => 'Halo'])->assertTooManyRequests();
    }

    public function test_upstream_error_body_is_not_logged(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.groq.com/*' => Http::response('private-user@example.com secret-token', 500)]);
        config(['services.groq.api_key' => 'fake-test-key']);
        Log::shouldReceive('warning')->once()->with('Groq AI API error', ['status' => 500]);

        $this->postJson('/api/chat', ['message' => 'Halo'])->assertOk()->assertJsonPath('success', true);
        Http::assertSentCount(1);
    }

    #[TestWith([['content' => ['unexpected' => 'value']]])]
    #[TestWith([['content' => '   ']])]
    public function test_invalid_provider_content_uses_offline_reply(array $message): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://api.groq.com/*' => Http::response(['choices' => [['message' => $message]]])]);
        config(['services.groq.api_key' => 'fake-test-key']);

        $response = $this->postJson('/api/chat', ['message' => 'paket'])->assertOk();
        $this->assertStringContainsString('Greece Tour', $response->json('message'));
        Http::assertSentCount(1);
    }

    public function test_client_token_cannot_append_messages_to_an_existing_chat_session(): void
    {
        config(['services.groq.api_key' => null]);
        $victim = ChatSession::create([
            'public_token' => str_repeat('a', 64),
            'model' => 'test-model',
            'expires_at' => now()->addWeek(),
        ]);

        $this->postJson('/api/chat', ['message' => 'paket', 'session_token' => $victim->public_token])->assertOk();
        $this->assertDatabaseMissing('chat_messages', ['session_id' => $victim->id]);
        $this->assertDatabaseCount('chat_messages', 2);
    }

    #[TestWith(['/contact'])]
    #[TestWith(['/bookings'])]
    public function test_public_write_endpoints_limit_invalid_requests(string $endpoint): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson($endpoint, [])->assertUnprocessable();
        }
        $this->postJson($endpoint, [])->assertTooManyRequests();
    }

    public function test_https_responses_include_hsts(): void
    {
        $this->getJson('https://localhost/api/health')->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        $this->getJson('http://localhost/api/health')->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security');
    }
}
