<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    /**
     * Check if the chat service is configured.
     */
    public function health(): JsonResponse
    {
        return response()
            ->json([
                'success' => true,
                'chatConfigured' => $this->chatService->isConfigured(),
            ])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Process an incoming chat message.
     */
    public function chat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:5000'],
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:5000'],
            'session_token' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_INPUT',
                    'message' => $firstError ?: 'Invalid chat request.',
                ],
                'errorMessage' => $firstError ?: 'Invalid chat request.',
            ], 400);
        }

        $validated = $validator->validated();
        $message = $validated['message'];
        $history = $validated['history'] ?? [];
        $sessionToken = isset($validated['session_token'])
            ? hash_hmac('sha256', $request->session()->getId().'|'.$validated['session_token'], config('app.key'))
            : null;

        $reply = $this->chatService->reply($message, $history, $sessionToken);

        return response()->json([
            'success' => true,
            'message' => $reply,
            'data' => [
                'message' => $reply,
            ],
        ]);
    }
}
