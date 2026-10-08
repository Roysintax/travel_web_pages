<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_renders_successfully_with_database_content(): void
    {
        $page = Page::create([
            'slug' => 'about',
            'html_file' => 'about.html',
            'title' => 'About Us — Travel Around the World',
            'meta_description' => 'Meet Travel: a brighter way to discover destinations.',
        ]);

        Faq::create([
            'page_id' => $page->id,
            'question' => 'How can I plan my trip?',
            'answer' => 'Use our interactive planner to customize your holiday.',
            'sort_order' => 1,
        ]);

        Faq::create([
            'page_id' => $page->id,
            'question' => 'Are flights included?',
            'answer' => 'Yes, flights and transfers are included in selected packages.',
            'sort_order' => 2,
        ]);

        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('About Us — Travel Around the World', false);
        $response->assertSee('About Travel — Holiday made simple, smart and connected', false);
        $response->assertSee('Your entire journey.', false);
        $response->assertSee('A little more connected.', false);
        $response->assertSee('Thoughtful travel.', false);
        $response->assertSee('Unforgettable holidays.', false);
        $response->assertSee('A world of possibility.', false);
        $response->assertSee('TRAVEL FAQ', false);
        $response->assertSee('How can I plan my trip?', false);
        $response->assertSee('Use our interactive planner to customize your holiday.', false);
        $response->assertSee('holiday-background.mp4', false);
        $response->assertSee('assets/about/destinations.jpg', false);
    }

    public function test_about_page_renders_with_default_faqs_when_database_is_empty(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('What is Travel?', false);
        $response->assertSee('How can I find a destination that suits me?', false);
        $response->assertSee('Does Travel work on mobile?', false);
    }

    public function test_legacy_about_html_redirects_permanently(): void
    {
        $response = $this->get('/about.html');

        $response->assertRedirect('/about');
    }

    public function test_about_page_contains_interactive_script_and_styles(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('about.css', false);
        $response->assertSee('about.js', false);
        $response->assertSee('about-page', false);
    }
}
