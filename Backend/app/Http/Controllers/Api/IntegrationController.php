<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DataSource;
use App\Services\Demand\DataProviderFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IntegrationController extends Controller
{
    private const PROVIDER_TYPES = [
        'google_trends',
        'google_ads',
        'search_console',
        'webhook',
        'africas_talking',
    ];

    public function __construct(
        private readonly DataProviderFactory $factory,
    ) {}

    // ── CRUD ─────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/integrations
     */
    public function index(Request $request): JsonResponse
    {
        $sources = DataSource::where('organization_id', $request->user()->organization_id)
            ->orderBy('type')
            ->get()
            ->map(fn ($s) => $this->safeSource($s));

        return response()->json([
            'data'       => $sources,
            'available'  => $this->availableProviders(),
        ]);
    }

    /**
     * POST /api/v1/integrations
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'type'        => ['required', Rule::in(self::PROVIDER_TYPES)],
            'credentials' => ['nullable', 'array'],
        ]);

        $source = DataSource::create([
            'organization_id' => $request->user()->organization_id,
            'name'            => $data['name'],
            'type'            => $data['type'],
            'status'          => 'active',
        ]);

        if (!empty($data['credentials'])) {
            $source->setCredentials($data['credentials']);
            $source->save();
        }

        AuditLog::record('integration.created', $source, ['type' => $source->type]);

        return response()->json($this->safeSource($source), 201);
    }

    /**
     * PATCH /api/v1/integrations/{source}
     */
    public function update(Request $request, DataSource $dataSource): JsonResponse
    {
        $this->authorizeSource($request, $dataSource);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'credentials' => ['nullable', 'array'],
            'status'      => ['sometimes', Rule::in(['active', 'paused', 'error'])],
        ]);

        if (isset($data['credentials'])) {
            $dataSource->setCredentials($data['credentials']);
            unset($data['credentials']);
        }

        $dataSource->update(array_filter($data, fn ($v) => $v !== null));

        AuditLog::record('integration.updated', $dataSource, ['type' => $dataSource->type]);

        return response()->json($this->safeSource($dataSource->fresh()));
    }

    /**
     * DELETE /api/v1/integrations/{source}
     */
    public function destroy(Request $request, DataSource $dataSource): JsonResponse
    {
        $this->authorizeSource($request, $dataSource);

        AuditLog::record('integration.deleted', $dataSource, ['type' => $dataSource->type]);
        $dataSource->delete();

        return response()->json(['message' => 'Integration removed.']);
    }

    // ── Test connection ───────────────────────────────────────────────────────

    /**
     * POST /api/v1/integrations/{source}/test
     * Performs a lightweight connectivity check for the provider.
     */
    public function test(Request $request, DataSource $dataSource): JsonResponse
    {
        $this->authorizeSource($request, $dataSource);

        $result = match ($dataSource->type) {
            'google_trends'   => $this->testGoogleTrends(),
            'africas_talking' => $this->testAfricasTalking($dataSource),
            'webhook'         => $this->testWebhook($dataSource),
            default           => ['ok' => true, 'message' => 'Provider does not support connection testing.'],
        };

        $status = $result['ok'] ? 'active' : 'error';
        $dataSource->update([
            'status'    => $status,
            'sync_meta' => array_merge($dataSource->sync_meta ?? [], [
                'last_test'    => now()->toDateTimeString(),
                'test_result'  => $result['message'],
            ]),
        ]);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    // ── Usage stats ───────────────────────────────────────────────────────────

    /**
     * GET /api/v1/integrations/{source}/stats
     */
    public function stats(Request $request, DataSource $dataSource): JsonResponse
    {
        $this->authorizeSource($request, $dataSource);

        $keywordCount = \App\Models\Keyword::whereHas('project',
                fn ($q) => $q->where('organization_id', $request->user()->organization_id))
            ->where('status', 'active')
            ->count();

        $measurementCount = \App\Models\KeywordMeasurement::whereHas('keyword.project',
                fn ($q) => $q->where('organization_id', $request->user()->organization_id))
            ->where('source', $dataSource->type)
            ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->count();

        return response()->json([
            'source'           => $this->safeSource($dataSource),
            'keywords_tracked' => $keywordCount,
            'measurements_30d' => $measurementCount,
            'last_sync_at'     => $dataSource->last_sync_at,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function testGoogleTrends(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'DemandLeadBot/1.0'])
                ->get('https://trends.google.com/trending/rss?geo=KE');

            return $response->successful()
                ? ['ok' => true,  'message' => 'Google Trends is reachable.']
                : ['ok' => false, 'message' => "Google Trends returned HTTP {$response->status()}."];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Cannot reach Google Trends: ' . $e->getMessage()];
        }
    }

    private function testAfricasTalking(DataSource $source): array
    {
        $creds    = $source->getCredentials();
        $apiKey   = $creds['api_key']  ?? env('AT_API_KEY');
        $username = $creds['username'] ?? env('AT_USERNAME');

        if (!$apiKey || !$username) {
            return ['ok' => false, 'message' => 'Africa\'s Talking credentials not configured.'];
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => $apiKey, 'Accept' => 'application/json',
            ])->get("https://api.africastalking.com/version1/user?username={$username}");

            return $response->successful()
                ? ['ok' => true,  'message' => "Africa's Talking connected. Username: {$username}"]
                : ['ok' => false, 'message' => "Africa's Talking returned HTTP {$response->status()}."];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Cannot reach Africa\'s Talking: ' . $e->getMessage()];
        }
    }

    private function testWebhook(DataSource $source): array
    {
        $creds = $source->getCredentials();
        $url   = $creds['url'] ?? null;

        if (!$url) {
            return ['ok' => false, 'message' => 'Webhook URL not configured.'];
        }

        try {
            $response = Http::timeout(8)->post($url, [
                'event' => 'test', 'source' => 'demandlead', 'timestamp' => now()->toIso8601String(),
            ]);
            return $response->successful()
                ? ['ok' => true,  'message' => "Webhook responded with HTTP {$response->status()}."]
                : ['ok' => false, 'message' => "Webhook returned HTTP {$response->status()}."];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Webhook test failed: ' . $e->getMessage()];
        }
    }

    /** Strip encrypted_credentials from the response */
    private function safeSource(DataSource $source): array
    {
        return [
            'id'           => $source->id,
            'name'         => $source->name,
            'type'         => $source->type,
            'status'       => $source->status,
            'last_sync_at' => $source->last_sync_at,
            'sync_meta'    => $source->sync_meta,
            'has_credentials' => !empty($source->encrypted_credentials),
            'created_at'   => $source->created_at,
        ];
    }

    private function authorizeSource(Request $request, DataSource $source): void
    {
        if ((int) $source->organization_id !== (int) $request->user()->organization_id) {
            abort(403);
        }
    }

    private function availableProviders(): array
    {
        return [
            ['type' => 'google_trends',   'label' => 'Google Trends',     'icon' => 'trending_up',   'desc' => 'Public demand signal data. No credentials required.'],
            ['type' => 'google_ads',       'label' => 'Google Ads',         'icon' => 'ads_click',     'desc' => 'Search volume and CPC data via Google Ads API.'],
            ['type' => 'search_console',   'label' => 'Search Console',     'icon' => 'manage_search', 'desc' => 'Impressions and click data from Google Search Console.'],
            ['type' => 'africas_talking',  'label' => "Africa's Talking",   'icon' => 'sms',           'desc' => 'SMS and USSD alerts for the East Africa market.'],
            ['type' => 'webhook',          'label' => 'Outbound Webhook',   'icon' => 'webhook',       'desc' => 'Send opportunity signals to any HTTP endpoint.'],
        ];
    }
}
