<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;
use Illuminate\View\View;

class AboutController extends Controller
{
    /**
     * Display the About Us page with story, features, and FAQs.
     */
    public function index(): View
    {
        $page = Page::with('faqs')->where('slug', 'about')->first();

        $defaultFaqs = [
            [
                'question' => 'What is Travel?',
                'answer' => 'Travel is a place to discover destinations and explore holiday packages, with a cinematic introduction that brings the journey to life.',
            ],
            [
                'question' => 'How can I find a destination that suits me?',
                'answer' => 'Visit our Destinations page to search by country or destination and filter by region. Each card includes a starting price to help you compare the options shown.',
            ],
            [
                'question' => 'Can I book a trip directly on this website?',
                'answer' => 'The current booking form is a preview. You can enter trip preferences and explore packages, but it does not make reservations or collect payment.',
            ],
            [
                'question' => 'Does Travel work on mobile?',
                'answer' => 'Yes. The website adapts to phones, tablets, and desktops. You can explore directly in your browser without downloading an app.',
            ],
            [
                'question' => 'Can I skip the cinematic introduction?',
                'answer' => 'Yes. Select “Skip journey” on Home to go straight to the travel content. The introduction also respects your device’s reduced motion setting.',
            ],
            [
                'question' => 'Where should I start?',
                'answer' => 'Start with Destinations for inspiration, or browse the packages on Home to compare featured trips and their durations.',
            ],
        ];

        $faqs = ($page && $page->faqs->isNotEmpty())
            ? $page->faqs
            : collect(array_map(fn ($item, $idx) => new Faq([
                'id' => $idx + 1,
                'question' => $item['question'],
                'answer' => $item['answer'],
                'sort_order' => $idx + 1,
            ]), $defaultFaqs, array_keys($defaultFaqs)));

        return view('about', compact('page', 'faqs'));
    }
}
