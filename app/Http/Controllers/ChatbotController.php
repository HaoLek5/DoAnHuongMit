<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function __construct(
        protected ChatbotService $chatbotService
    ) {}

    /**
     * Handle incoming chatbot message.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $response = $this->chatbotService->processMessage($validated['message']);

        return response()->json($response);
    }

    /**
     * Get default prompt suggestion chips.
     */
    public function getSuggestions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'chips' => $this->chatbotService->getDefaultChips(),
        ]);
    }
}
