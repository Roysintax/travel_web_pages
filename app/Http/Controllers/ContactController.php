<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * Display the Contact Us page with communication channels and interactive live chat.
     */
    public function index(): View
    {
        $page = Page::where('slug', 'contact')->first();

        $defaultSettings = [
            'brand' => 'Travel',
            'phone' => '+62 21 5000 1234',
            'whatsapp' => '+62 812 0000 1234',
            'email' => 'hello@travel.example',
            'office_address' => 'Jl. Jend. Sudirman Kav. 21, Jakarta 12920, Indonesia',
            'office_hours' => 'Mon–Sat, 08:00–20:00 WIB',
            'office_timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
        ];

        $dbSettings = SiteSetting::allKeyValues();
        $settings = array_merge($defaultSettings, $dbSettings);

        return view('contact', compact('page', 'settings'));
    }

    /**
     * Store a submitted contact message in the database.
     */
    public function store(StoreContactMessageRequest $request): JsonResponse|RedirectResponse
    {
        $replyChannel = $request->validated('reply') ?? 'Email';

        $contactMessage = ContactMessage::create([
            'full_name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'topic' => $request->input('topic'),
            'reply_channel' => $replyChannel,
            'message' => $request->input('message'),
            'status' => 'new',
        ]);

        $firstName = explode(' ', trim($request->input('name')))[0];
        $successMessage = "Thanks, {$firstName}! A travel expert will reply via ".strtolower($replyChannel)." about “{$request->input('topic')}” soon.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => [
                    'id' => $contactMessage->id,
                    'full_name' => $contactMessage->full_name,
                    'topic' => $contactMessage->topic,
                    'reply_channel' => $contactMessage->reply_channel,
                ],
            ]);
        }

        return redirect()->route('contact')->with('success', $successMessage);
    }
}
