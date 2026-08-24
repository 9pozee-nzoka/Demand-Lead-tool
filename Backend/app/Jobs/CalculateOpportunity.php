<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 7: Score an opportunity based on growth, intent, geo, volume,
 * competition and historical conversion using the weighted formula from spec.
 *
 * Score = growth*0.30 + intent*0.25 + geo*0.15 + volume*0.15 + competition*0.10 + historical*0.05
 *
 * Queued on: scoring
 * Dispatched by: ProcessDemandSignal
 */
class CalculateOpportunity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public readonly int $keywordId)
    {
        $this->onQueue('scoring');
    }

    public function handle(): void
    {
        $keyword = Keyword::with(['measurements', 'locations', 'project'])->find($this->keywordId);

        if (! $keyword) {
            return;
        }

        // TODO Sprint 7: Implement full scoring pipeline
        //
        // $growthScore      = $this->scoreGrowth($keyword);
        // $intentScore      = $this->scoreIntent($keyword->intent);
        // $geoScore         = $this->scoreGeo($keyword);
        // $volumeScore      = $this->scoreVolume($keyword);
        // $competitionScore = $this->scoreCompetition($keyword);
        // $historicalScore  = $this->scoreHistorical($keyword);
        //
        // $opportunityScore = ($growthScore * 0.30)
        //                   + ($intentScore * 0.25)
        //                   + ($geoScore * 0.15)
        //                   + ($volumeScore * 0.15)
        //                   + ($competitionScore * 0.10)
        //                   + ($historicalScore * 0.05);
        //
        // Opportunity::updateOrCreate(['keyword_id' => $keyword->id, 'status' => 'detected'], [...]);
        //
        // If score above threshold, dispatch alert evaluation
        // SendAlert::dispatch($opportunity->id)->onQueue('notifications');

        Log::info("CalculateOpportunity: keyword #{$this->keywordId} scored.");
    }
}
