<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DataSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class IntegrationController extends Controller
{
    private const PROVIDER_TYPES = [
        'google_trends',
        'google_ads',
        'search_console',
        'webhook',
        'africas_talking',
        'openai',
    ];

    /**
     * Display integrations dashboard
     */
    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        $sources = DataSource::where('organization_id', $organizationId)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $availableProviders = $this->getAvailableProviders();

        return view('integrations.index', compact('sources', 'availableProviders'));
    }

    /**
     * Show create integration form
     */
    public function create(Request $request)
    {
        $type = $request->get('type');
        $availableProviders = $this->getAvailableProviders();

        if ($type && !in_array($type, self::PROVIDER_TYPES)) {
            return redirect()->route('integrations.index')
                ->withErrors(['error' => 'Invalid provider type.']);
        }

        return view('integrations.create', compact('type', 'availableProviders'));
    }

    /**
     * Store new integration
     */
    public function store(Request $request)
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

        return redirect()->route('integrations.index')
            ->with('success', 'Integration created successfully!');
    }

    /**
     * Show integration details
     */
    public function show(DataSource $dataSource)
    {
        $this->authorize($dataSource);

        $keywordCount = \App\Models\Keyword::whereHas('project', 
            fn ($q) => $q->where('organization_id', $dataSource->organization_id))
            ->where('status', 'active')
            ->count();

        $measurementCount = \App\Models\KeywordMeasurement::whereHas('keyword.project',
            fn ($q) => $q->where('organization_id', $dataSource->organization_id))
            ->where('source', $dataSource->type)
            ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->count();

        $recentMeasurements = \App\Models\KeywordMeasurement::whereHas('keyword.project',
            fn ($q) => $q->where('organization_id', $dataSource->organization_id))
            ->where('source', $dataSource->type)
            ->with('keyword')
            ->latest()
            ->limit(20)
            ->get();

        return view('integrations.show', compact('dataSource', 'keywordCount', 'measurementCount', 'recentMeasurements'));
    }

    /**
     * Show edit form
     */
    public function edit(DataSource $dataSource)
    {
        $this->authorize($dataSource);

        $availableProviders = $this->getAvailableProviders();
        $provider = collect($availableProviders)->firstWhere('type', $dataSource->type);

        return view('integrations.edit', compact('dataSource', 'provider'));
    }

    /**
     * Update integration
     */
    public function update(Request $request, DataSource $dataSource)
    {
        $this->authorize($dataSource);

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

        return redirect()->route('integrations.show', $dataSource)
            ->with('success', 'Integration updated successfully!');
    }

    /**
     * Test connection
     */
    public function test(Request $request, DataSource $dataSource)
    {
        $this->authorize($dataSource);

        $result = match ($dataSource->type) {
            'google_trends'   => $this->testGoogleTrends(),
            'africas_talking' => $this->testAfricasTalking($dataSource),
            'webhook'         => $this->testWebhook($dataSource),
            'openai'          => $this->testOpenAI($dataSource),
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

        if ($result['ok']) {
            return back()->with('success', $result['message']);
        } else {
            return back()->withErrors(['error' => $result['message']]);
        }
    }

    /**
     * Pause integration
     */
    public function pause(DataSource $dataSource)
    {
        $this->authorize($dataSource);

        $dataSource->update(['status' => 'paused']);

        AuditLog::record('integration.paused', $dataSource);

        return back()->with('success', 'Integration paused successfully.');
    }

    /**
     * Activate integration
     */
    public function activate(DataSource $dataSource)
    {
        $this->authorize($dataSource);

        $dataSource->update(['status' => 'active']);

        AuditLog::record('integration.activated', $dataSource);

        return back()->with('success', 'Integration activated successfully.');
    }

    /**
     * Delete integration
     */
    public function destroy(DataSource $dataSource)
    {
        $this->authorize($dataSource);

        AuditLog::record('integration.deleted', $dataSource, ['type' => $dataSource->type]);

        $dataSource->delete();

        return redirect()->route('integrations.index')
            ->with('success', 'Integration deleted successfully.');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function authorize(DataSource $source): void
    {
        if ((int) $source->organization_id !== (int) auth()->user()->organization_id) {
            abort(403);
        }
    }

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

    private function testOpenAI(DataSource $source): array
    {
        $creds = $source->getCredentials();
        $apiKey = $creds['api_key'] ?? env('OPENAI_API_KEY');

        if (!$apiKey) {
            return ['ok' => false, 'message' => 'OpenAI API key not configured.'];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get('https://api.openai.com/v1/models');

            return $response->successful()
                ? ['ok' => true, 'message' => 'OpenAI API connected successfully.']
                : ['ok' => false, 'message' => "OpenAI API returned HTTP {$response->status()}."];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Cannot reach OpenAI API: ' . $e->getMessage()];
        }
    }

    private function getAvailableProviders(): array
    {
        return [
            [
                'type'  => 'google_trends',
                'label' => 'Google Trends',
                'icon'  => 'trending_up',
                'desc'  => 'Public demand signal data. No credentials required.',
                'color' => 'blue',
            ],
            [
                'type'  => 'google_ads',
                'label' => 'Google Ads',
                'icon'  => 'ads_click',
                'desc'  => 'Search volume and CPC data via Google Ads API.',
                'color' => 'green',
            ],
            [
                'type'  => 'search_console',
                'label' => 'Search Console',
                'icon'  => 'manage_search',
                'desc'  => 'Impressions and click data from Google Search Console.',
                'color' => 'yellow',
            ],
            [
                'type'  => 'africas_talking',
                'label' => "Africa's Talking",
                'icon'  => 'sms',
                'desc'  => 'SMS and USSD alerts for the East Africa market.',
                'color' => 'purple',
            ],
            [
                'type'  => 'openai',
                'label' => 'OpenAI',
                'icon'  => 'psychology',
                'desc'  => 'AI-powered intent analysis, clustering, and content generation.',
                'color' => 'indigo',
            ],
            [
                'type'  => 'webhook',
                'label' => 'Outbound Webhook',
                'icon'  => 'webhook',
                'desc'  => 'Send opportunity signals to any HTTP endpoint.',
                'color' => 'gray',
            ],
        ];
    }
}
