<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScrapedItem;
use App\Models\SourceScraper;
use App\Services\Sources\Providers\WebhookSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Receive webhook submission
     */
    public function receive(Request $request, int $sourceId, WebhookSource $webhookSource)
    {
        // Find the source
        $source = SourceScraper::find($sourceId);

        if (!$source || $source->type !== 'webhook') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook source',
            ], 404);
        }

        // Check if source is active
        if (!$source->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook source is not active',
            ], 403);
        }

        // Get token from request
        $token = $request->input('token') 
            ?? $request->bearerToken() 
            ?? $request->header('X-Webhook-Token');

        // Validate authentication
        $payload = $request->all();
        if (!$webhookSource->validateWebhook($source, $payload, $token ?? '')) {
            Log::warning("Webhook authentication failed", [
                'source_id' => $sourceId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
            ], 401);
        }

        try {
            // Add request metadata
            $payload['ip_address'] = $request->ip();
            $payload['user_agent'] = $request->userAgent();
            $payload['timestamp'] = now()->toIso8601String();

            // Process the webhook
            $result = $webhookSource->processWebhook($source, $payload);

            // Create scraped item
            $item = ScrapedItem::create(array_merge($result['data'], [
                'source_scraper_id' => $source->id,
                'scrape_job_id' => null, // Webhooks don't have jobs
                'organization_id' => $source->organization_id,
                'intent' => 'transactional', // Webhooks are typically transactional
                'processing_status' => 'pending',
            ]));

            Log::info("Webhook received successfully", [
                'source_id' => $sourceId,
                'item_id' => $item->id,
            ]);

            // Dispatch processing jobs
            \App\Jobs\ProcessKeywordMatching::dispatch($source->organization);

            return response()->json([
                'success' => true,
                'message' => 'Lead received successfully',
                'id' => $item->id,
            ], 201);
        } catch (\Throwable $e) {
            Log::error("Webhook processing failed", [
                'source_id' => $sourceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process webhook',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test webhook endpoint
     */
    public function test(Request $request, int $sourceId)
    {
        $source = SourceScraper::find($sourceId);

        if (!$source || $source->type !== 'webhook') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook source',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook endpoint is operational',
            'source' => [
                'id' => $source->id,
                'name' => $source->name,
                'status' => $source->status,
            ],
        ]);
    }
}
