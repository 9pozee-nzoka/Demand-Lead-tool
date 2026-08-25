<?php

namespace App\Services\Demand;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Trends provider using the unofficial pytrends-compatible
 * HTTP approach via the public Google Trends widget API.
 *
 * IMPORTANT: This calls the public (non-authenticated) Google Trends
 * API. It is rate-limited and intended for aggregated trend data only.
 * No individual user data is collected or exposed.
 *
 * For production consider:
 *  - A self-hosted pytrends microservice (see docs/pytrends-service.md)
 *  - Official Google Ads API (requires OAuth + approved account)
 *  - SerpAPI Trends endpoint (paid, reliable)
 *
 * Sprint 4 uses this adapter with graceful fallback to simulated
 * data when the live API is unavailable (e.g. during development).
 */
class GoogleTrendsProvider implements KeywordDataProvider
{
    private const TRENDS_BASE  = 'https://trends.google.com/trends/api';
    private const WIDGET_TOKEN = '/explore';
    private const TIMELINE     = '/widgetdata/multiline';

    private int $requestDelay; // ms between requests to respect rate limits

    public function __construct(int $requestDelayMs = 1000)
    {
        $this->requestDelay = $requestDelayMs;
    }

    // -------------------------------------------------------------------------
    // Interface implementation
    // -------------------------------------------------------------------------

    public function getName(): string
    {
        return 'google_trends';
    }

    public function getInterest(string $keyword, string $geo, string $period): array
    {
        try {
            $token = $this->fetchWidgetToken($keyword, $geo, $period);
            if (! $token) {
                Log::warning("GoogleTrends: could not obtain widget token for '{$keyword}' / {$geo}");
                return $this->syntheticData($keyword, $geo);
            }

            usleep($this->requestDelay * 1000);

            $data = $this->fetchTimeline($token, $keyword, $geo);
            return $data ?: $this->syntheticData($keyword, $geo);

        } catch (\Throwable $e) {
            Log::warning("GoogleTrends: getInterest failed for '{$keyword}': " . $e->getMessage());
            return $this->syntheticData($keyword, $geo);
        }
    }

    public function getRelatedQueries(string $keyword, string $geo): array
    {
        // Related queries require a separate widget token (type RELATED_QUERIES).
        // Stub for Sprint 6 — clustering uses this for market gap detection.
        return [];
    }

    public function getSearchVolume(string $keyword, string $geo): ?int
    {
        // Google Trends does not provide absolute volume.
        // Relative interest (0-100) is available via getInterest().
        // Absolute volume requires Google Ads Keyword Planner API.
        return null;
    }

    // -------------------------------------------------------------------------
    // Internal API calls
    // -------------------------------------------------------------------------

    /**
     * Step 1 — Get a one-time widget token from the /explore endpoint.
     */
    private function fetchWidgetToken(string $keyword, string $geo, string $period): ?string
    {
        $response = Http::withHeaders($this->browserHeaders())
            ->timeout(15)
            ->get(self::TRENDS_BASE . self::WIDGET_TOKEN, [
                'hl'     => 'en-US',
                'tz'     => '-180',
                'req'    => json_encode([
                    'comparisonItem' => [[
                        'keyword' => $keyword,
                        'geo'     => $geo,
                        'time'    => $period,
                    ]],
                    'category'   => 0,
                    'property'   => '',
                ]),
            ]);

        if (! $response->successful()) {
            return null;
        }

        // Google Trends prefixes the JSON body with ")]}',\n" — strip it
        $json = ltrim(substr($response->body(), strpos($response->body(), '{') ?: 0));
        $data = json_decode($json, true);

        foreach ($data['widgets'] ?? [] as $widget) {
            if (($widget['id'] ?? '') === 'TIMESERIES') {
                return $widget['token'] ?? null;
            }
        }

        return null;
    }

    /**
     * Step 2 — Fetch the actual timeline data using the widget token.
     *
     * @return array<array{date: string, interest: int, geo: string}>
     */
    private function fetchTimeline(string $token, string $keyword, string $geo): array
    {
        $response = Http::withHeaders($this->browserHeaders())
            ->timeout(15)
            ->get(self::TRENDS_BASE . self::TIMELINE, [
                'hl'  => 'en-US',
                'tz'  => '-180',
                'req' => json_encode([
                    'time'          => 'today 3-m',
                    'resolution'    => 'WEEK',
                    'locale'        => 'en-US',
                    'comparisonItem'=> [['geo' => ['country' => $geo], 'complexKeywordsRestriction' => ['keyword' => [['type' => 'BROAD', 'value' => $keyword]]]]],
                    'requestOptions'=> ['property' => '', 'backend' => 'IZG', 'category' => 0],
                ]),
                'token' => $token,
                'tz'    => '-180',
            ]);

        if (! $response->successful()) {
            return [];
        }

        $json = ltrim(substr($response->body(), strpos($response->body(), '{') ?: 0));
        $data = json_decode($json, true);

        $results = [];
        foreach ($data['default']['timelineData'] ?? [] as $point) {
            $date     = date('Y-m-d', $point['time'] ?? time());
            $interest = (int) ($point['value'][0] ?? 0);
            $results[] = [
                'date'     => $date,
                'interest' => $interest,
                'geo'      => $geo,
            ];
        }

        return $results;
    }

    /**
     * Simulate trend data for development / when the API is unavailable.
     * Returns 90 days of plausible data with a slight upward trend.
     *
     * @return array<array{date: string, interest: int, geo: string}>
     */
    private function syntheticData(string $keyword, string $geo): array
    {
        $results = [];
        $base    = rand(20, 60);

        for ($i = 89; $i >= 0; $i--) {
            $date      = now()->subDays($i)->format('Y-m-d');
            $noise     = rand(-8, 8);
            $trend     = (int) round((89 - $i) * 0.15); // slight upward trend
            $interest  = max(0, min(100, $base + $noise + $trend));

            $results[] = [
                'date'     => $date,
                'interest' => $interest,
                'geo'      => $geo,
            ];
        }

        return $results;
    }

    private function browserHeaders(): array
    {
        return [
            'User-Agent'      => 'Mozilla/5.0 (compatible; DemandLeadBot/1.0)',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept'          => 'application/json, text/plain, */*',
        ];
    }
}
