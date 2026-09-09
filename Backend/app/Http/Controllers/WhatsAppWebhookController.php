<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Verify webhook (GET request from WhatsApp)
     */
    public function verify(Request $request): JsonResponse|string
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        Log::info('WhatsApp webhook verification attempt', [
            'mode' => $mode,
            'token' => $token ? 'present' : 'missing',
            'challenge' => $challenge ? 'present' : 'missing',
        ]);

        $result = $this->whatsappService->verifyWebhook($mode, $token, $challenge);

        if ($result) {
            return response($result, 200);
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    /**
     * Handle incoming webhooks (POST request from WhatsApp)
     */
    public function handle(Request $request): JsonResponse
    {
        $data = $request->all();

        Log::info('WhatsApp webhook received', [
            'data' => $data,
            'headers' => $request->headers->all(),
        ]);

        // Process webhook asynchronously to avoid timeout
        try {
            $this->whatsappService->processWebhook($data);
            
            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Still return 200 to WhatsApp to avoid retries
            return response()->json(['status' => 'error'], 200);
        }
    }
}
