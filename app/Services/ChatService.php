<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatService
{
    public const SYSTEM_PROMPT = <<<'EOT'
You are Nadia, the AI travel assistant on the Contact page of "Travel — Around the World", a holiday travel agency based in Jakarta, Indonesia.

How to reply:
- Reply in the same language the user writes in (Indonesian or English).
- Be warm, clear, and concise: usually 2-5 short sentences, max about 120 words.
- Use plain text only. No Markdown (no **, #, or tables). For lists use simple lines starting with "- ".
- Never invent prices, availability, booking status, or policies that are not listed below. If unsure, say so and suggest contacting the team.
- You cannot see or change real bookings. For booking changes, payments, or anything personal, direct the user to the human team.
- Politely decline requests unrelated to travel and steer back to trip planning.

Company facts:
- All package prices are in Indonesian Rupiah (IDR / Rp) and quoted per person:
  - Greece Tour (Santorini) — 6 days / 5 nights — mulai Rp 19.500.000
  - Maldives Escape — 5 days / 4 nights — mulai Rp 24.500.000
  - Japan Discovery (Kyoto) — 8 days / 7 nights — mulai Rp 33.500.000
  - Alberta, Canada (Canadian Rockies) — mulai Rp 27.500.000
  - Bali & Nusa Penida — 5 days / 4 nights — mulai Rp 14.900.000
  - Swiss Alps Panoramic — 7 days / 6 nights — mulai Rp 36.500.000
- Pricing Plans:
  - Economy Explorer: Rp 7.500.000 / orang
  - Standard Comfort: Rp 19.500.000 / orang
  - Luxury Platinum: Rp 33.500.000 / orang
- Website pages: Destinations (/destinations), Packages (/packages), Bookings (/bookings), About Us (/about), Contact (/contact).
- The website booking form is currently a preview; it does not take real reservations or payments.
- Phone: +62 21 5000 1234 (Mon-Sat, 08:00-20:00 WIB)
- WhatsApp: +62 812 0000 1234
- Email: hello@travel.example
- Office: Jl. Jend. Sudirman Kav. 21, Jakarta 12920 — 2 minutes walk from MRT Setiabudi Astra. Open Mon-Sat, 08:00-20:00 WIB.
EOT;

    /**
     * Check if AI service is configured with an API key.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.groq.api_key'));
    }

    /**
     * Generate response using Groq or intelligent offline fallback.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function reply(string $message, array $history = [], ?string $sessionToken = null): string
    {
        $cleanMessage = trim($message);
        $apiKey = config('services.groq.api_key');
        $model = config('services.groq.model', 'openai/gpt-oss-120b');
        $apiUrl = config('services.groq.api_url', 'https://api.groq.com/openai/v1/chat/completions');
        $timeout = (int) config('services.groq.timeout', 25);

        $reply = null;

        if (! empty($apiKey)) {
            try {
                $messages = [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ];

                foreach ($history as $item) {
                    if (isset($item['role'], $item['content']) && in_array($item['role'], ['user', 'assistant'], true)) {
                        $messages[] = [
                            'role' => $item['role'],
                            'content' => mb_substr(trim($item['content']), 0, 5000),
                        ];
                    }
                }

                $messages[] = [
                    'role' => 'user',
                    'content' => $cleanMessage,
                ];

                $response = Http::withToken($apiKey)
                    ->connectTimeout(5)
                    ->timeout($timeout)
                    ->withoutRedirecting()
                    ->post($apiUrl, [
                        'model' => $model,
                        'messages' => $messages,
                        'max_tokens' => 1000,
                        'temperature' => 0.6,
                    ]);

                if ($response->successful()) {
                    $content = $response->json('choices.0.message.content');
                    if (is_string($content) && trim($content) !== '') {
                        $reply = trim($content);
                    }
                } else {
                    Log::warning('Groq AI API error', ['status' => $response->status()]);
                }
            } catch (\Throwable $e) {
                Log::warning('Groq AI request exception', ['exception' => $e::class]);
            }
        }

        // Offline / graceful fallback if API is unreachable or not configured
        if ($reply === null) {
            $reply = $this->fallbackReply($cleanMessage);
        }

        // Optionally record in database if sessionToken provided
        $this->recordMessage($cleanMessage, $reply, $sessionToken, $model);

        return $reply;
    }

    /**
     * Intelligent local fallback reply when AI service is unavailable.
     */
    protected function fallbackReply(string $message): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'paket') || str_contains($lower, 'package') || str_contains($lower, 'destinasi') || str_contains($lower, 'destination')) {
            return 'Kami menyediakan paket pilihan seperti Greece Tour (Santorini) mulai Rp 19.500.000, Maldives Escape mulai Rp 24.500.000, Bali & Nusa Penida mulai Rp 14.900.000, Japan Discovery mulai Rp 33.500.000, dan Swiss Alps mulai Rp 36.500.000. Anda bisa melihat rincian lengkapnya di halaman Packages atau Destinations!';
        }

        if (str_contains($lower, 'harga') || str_contains($lower, 'biaya') || str_contains($lower, 'price') || str_contains($lower, 'cost')) {
            return 'Paket perjalanan kami berkisar antara Rp 14.900.000 hingga Rp 36.500.000 per orang sesuai destinasi dan durasi. Tersedia juga opsi paket hemat Economy Explorer mulai Rp 7.500.000 / orang.';
        }

        if (str_contains($lower, 'booking') || str_contains($lower, 'pesan') || str_contains($lower, 'reservasi')) {
            return 'Formulir pemesanan di website saat ini adalah preview untuk merencanakan estimasi biaya. Untuk reservasi resmi dan konsultasi jadwal, silakan hubungi tim kami via WhatsApp di +62 812 0000 1234 atau telepon +62 21 5000 1234.';
        }

        if (str_contains($lower, 'visa') || str_contains($lower, 'paspor') || str_contains($lower, 'dokumen')) {
            return 'Tim kami menyediakan bantuan pengurusan visa wisata untuk destinasi Schengen (Yunani), Jepang, dan Kanada, termasuk panduan dokumen serta jadwal temu kedutaan.';
        }

        if (str_contains($lower, 'kantor') || str_contains($lower, 'lokasi') || str_contains($lower, 'alamat') || str_contains($lower, 'office')) {
            return 'Kantor kami beralamat di Jl. Jend. Sudirman Kav. 21, Jakarta 12920 (hanya 2 menit jalan kaki dari MRT Setiabudi Astra). Buka Senin–Sabtu pukul 08:00–20:00 WIB.';
        }

        return 'Halo! Saya Nadia dari Travel. Ada yang bisa saya bantu untuk merencanakan liburan impian Anda ke Santorini, Jepang, Swiss, Maldives, atau Bali? Silakan tanyakan seputar destinasi, perkiraan biaya, atau jadwal perjalanan!';
    }

    /**
     * Persist chat session and messages if session token is provided.
     */
    protected function recordMessage(string $userText, string $assistantReply, ?string $token, string $model): void
    {
        if (empty($token)) {
            return;
        }

        try {
            $session = ChatSession::firstOrCreate(
                ['public_token' => $token],
                [
                    'model' => $model,
                    'expires_at' => now()->addDays(7),
                ]
            );

            ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'user',
                'content' => $userText,
            ]);

            ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $assistantReply,
            ]);
        } catch (\Throwable $e) {
            Log::info('Failed to record chat message', ['exception' => $e::class]);
        }
    }
}
