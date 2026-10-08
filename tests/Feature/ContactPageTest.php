<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_renders_successfully_with_default_settings(): void
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('Let’s plan it', false);
        $response->assertSee('together.', false);
        $response->assertSee('Ways to reach us', false);
        $response->assertSee('Tell us about your trip', false);
        $response->assertSee('Nadia · AI Travel Assistant', false);
        $response->assertSee('Drop by for a coffee &amp; a plan.', false);
        $response->assertSee('+62 21 5000 1234', false);
        $response->assertSee('hello@travel.example', false);
    }

    public function test_contact_page_uses_database_page_and_site_settings(): void
    {
        Page::create([
            'slug' => 'contact',
            'html_file' => 'contact.html',
            'title' => 'Custom Contact Title — Travel',
            'meta_description' => 'Custom meta description for contact page.',
        ]);

        SiteSetting::create([
            'setting_key' => 'phone',
            'setting_value' => '+62 21 9999 8888',
        ]);

        SiteSetting::create([
            'setting_key' => 'email',
            'setting_value' => 'vip@travel.example',
        ]);

        SiteSetting::create([
            'setting_key' => 'office_address',
            'setting_value' => 'Jl. Thamrin No. 99, Jakarta',
        ]);

        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('Custom Contact Title — Travel', false);
        $response->assertSee('+62 21 9999 8888', false);
        $response->assertSee('vip@travel.example', false);
        $response->assertSee('Jl. Thamrin No. 99, Jakarta', false);
    }

    public function test_contact_message_can_be_stored_via_ajax(): void
    {
        $payload = [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '+628123456789',
            'topic' => 'Plan a new trip',
            'reply' => 'WhatsApp',
            'message' => 'Saya ingin bertanya tentang paket liburan ke Swiss untuk 2 orang.',
        ];

        $response = $this->postJson('/contact', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('data.full_name', 'Budi Santoso');
        $response->assertJsonPath('data.topic', 'Plan a new trip');
        $response->assertJsonPath('data.reply_channel', 'WhatsApp');

        $this->assertDatabaseHas('contact_messages', [
            'full_name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '+628123456789',
            'topic' => 'Plan a new trip',
            'reply_channel' => 'WhatsApp',
            'status' => 'new',
        ]);
    }

    public function test_contact_message_can_be_stored_via_standard_form(): void
    {
        $payload = [
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@example.com',
            'phone' => '+628129876543',
            'topic' => 'Packages & pricing',
            'reply' => 'Email',
            'message' => 'Berapa biaya tambahan untuk kamar single di Santorini?',
        ];

        $response = $this->post('/contact', $payload);

        $response->assertRedirect('/contact');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'full_name' => 'Siti Nurhaliza',
            'email' => 'siti@example.com',
            'topic' => 'Packages & pricing',
            'reply_channel' => 'Email',
        ]);
    }

    public function test_contact_message_validation_rejects_invalid_inputs(): void
    {
        $response = $this->postJson('/contact', [
            'name' => 'A', // too short (min 2)
            'email' => 'not-an-email',
            'topic' => 'Plan a new trip',
            'message' => 'Short', // too short (min 10)
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_legacy_contact_html_redirects_permanently(): void
    {
        $response = $this->get('/contact.html');

        $response->assertRedirect('/contact');
    }

    public function test_null_reply_channel_defaults_to_email(): void
    {
        $this->postJson('/contact', [
            'name' => 'Test Traveler',
            'email' => 'traveler@example.com',
            'topic' => 'Travel plans',
            'reply' => null,
            'message' => 'Please help me plan my next trip.',
        ])->assertOk()->assertJsonPath('data.reply_channel', 'Email');
        $this->assertDatabaseHas('contact_messages', ['email' => 'traveler@example.com', 'reply_channel' => 'Email']);
    }
}
