<?php

namespace App\Jobs;

use App\Models\DataSource;
use App\Models\Keyword;
use App\Models\KeywordLocation;
use App\Models\KeywordMeasurement;
use App\Services\Demand\DataProviderFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 4 — Fetch permitted demand data for one keyword from all
 * active DataSources belonging to its organization. If the org has no
 * configured sources the default Google Trends public adapter is used.
 *
 * One job per keyword keeps retries granular and prevents a single
 * slow keyword from blocking others.
 *
 * Queue : ingestion
 * Chains: ProcessDemandSignal (processing queue) on completion
 */
class CollectKeywordData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    /** Exponential backoff: 30 s, 5 min, 30 min */
    public function backoff(): array
    {
        return [30, 300, 1800];
    }

    public function __construct(public readonly int $keywordId)
    {
        $this->onQueue('ingestion');
    }

    // -------------------------------------------------------------------------

    public function handle(DataProviderFactory $factory): void
    {
        $keyword = Keyword::with([
            'locations',
            'project.organization.dataSources' => fn ($q) => $q->where('status', 'active'),
        ])->find($this->keywordId);

        if (! $keyword || $keyword->status !== 'active') {
            return;
        }

        $orgId    = $keyword->project->organization_id;
        $sources  = $keyword->project->organization->dataSources ?? collect();

        // Use configured sources; fall back to the default public provider
        $providers = $sources->isNotEmpty()
            ? $sources->map(fn ($s) => [$s, $factory->make($s)])
            : collect([[null, $factory->makeDefault()]]);

        $locations = $keyword->locations;

        // If no locations configured, use the project's default country
        if ($locations->isEmpty()) {
            $defaultGeo = $keyword->project->country ?? 'KE';
            $locations  = collect([
                (object) ['country' => $defaultGeo, 'city' => null, 'region' => null, 'id' => null],
            ]);
        }

        $measurementCount = 0;

        foreach ($providers as [$source, $provider]) {
            foreach ($locations as $location) {
                $geo = $location->country ?? 'KE';

                try {
                    $dataPoints = $provider->getInterest(
                        keyword: $keyword->normalized_keyword,
                        geo:     $geo,
                        period:  'today 3-m',
                    );

                    foreach ($dataPoints as $point) {
                        // Upsert so re-runs don't create duplicate rows
                        KeywordMeasurement::updateOrCreate(
                            [
                                'keyword_id' => $keyword->id,
                                'source'     => $provider->getName(),
                                'date'       => $point['date'],
                                'geo'        => $geo,
                            ],
                            [
                                'interest'  => $point['interest'],
                                'volume'    => null,   // Sprint 4: Google Trends gives relative index only
                                'growth'    => null,   // Sprint 5: computed by ProcessDemandSignal
                                'raw_data'  => $point,
                            ]
                        );
                        $measurementCount++;
                    }

                    // Update the data source's last sync timestamp
                    if ($source) {
                        $source->update([
                            'status'       => 'active',
                            'last_sync_at' => now(),
                            'sync_meta'    => ['keyword_id' => $keyword->id, 'records' => count($dataPoints)],
                        ]);
                    }

                } catch (\Throwable $e) {
                    Log::error("CollectKeywordData: provider {$provider->getName()} failed for keyword #{$this->keywordId} / {$geo}: " . $e->getMessage());

                    if ($source) {
                        $source->update([
                            'status'    => 'error',
                            'sync_meta' => ['error' => $e->getMessage(), 'keyword_id' => $keyword->id],
                        ]);
                    }
                }
            }
        }

        Log::info("CollectKeywordData: keyword #{$this->keywordId} collected {$measurementCount} measurements.");

        // Chain: compute baselines + growth %
        if ($measurementCount > 0) {
            ProcessDemandSignal::dispatch($this->keywordId)->onQueue('processing');
        }
    }
}
