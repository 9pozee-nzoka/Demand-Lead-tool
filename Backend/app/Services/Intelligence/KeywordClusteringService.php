<?php

namespace App\Services\Intelligence;

use App\Models\DemandCluster;
use App\Models\Keyword;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Sprint 13 — Groups keywords into semantic demand clusters.
 *
 * Phase 1 (now): Rule-based clustering using shared root words and
 *                intent + category matching. Fast and zero-cost.
 * Phase 2: Replace with embedding-based clustering (OpenAI text-embedding-3-small
 *          cosine similarity) for Sprint 17.
 *
 * A cluster represents a market theme:
 *   "solar installation Nairobi" + "solar panels price Kenya" + "solar installer near me"
 *   → cluster: "Solar Installation — Nairobi" (transactional, high-score)
 */
class KeywordClusteringService
{
    private const MIN_CLUSTER_SIZE = 2;
    private const STOPWORDS = ['in', 'of', 'the', 'a', 'an', 'and', 'or', 'for', 'to', 'with'];

    public function __construct(
        private readonly IntentClassificationService $intent,
    ) {}

    // -------------------------------------------------------------------------

    /**
     * Cluster all active keywords for a project and persist DemandCluster records.
     *
     * @return int Number of clusters created or updated
     */
    public function clusterProject(int $projectId): int
    {
        $keywords = Keyword::where('project_id', $projectId)
            ->where('status', 'active')
            ->get();

        if ($keywords->isEmpty()) {
            return 0;
        }

        $groups = $this->groupByRootWords($keywords);
        $saved  = 0;

        foreach ($groups as $rootWord => $clusterKeywords) {
            if (count($clusterKeywords) < self::MIN_CLUSTER_SIZE) {
                continue;
            }

            $dominantIntent   = $this->dominantIntent($clusterKeywords);
            $avgScore         = $clusterKeywords
                ->flatMap(fn ($k) => $k->measurements)
                ->avg('growth') ?? 0;

            $cluster = DemandCluster::updateOrCreate(
                ['project_id' => $projectId, 'name' => $this->clusterName($rootWord, $clusterKeywords)],
                [
                    'category' => $clusterKeywords->first()->category ?? null,
                    'intent'   => $dominantIntent,
                    'score'    => max(0, round($avgScore, 2)),
                    'status'   => 'active',
                ]
            );

            // Sync keywords to cluster
            $cluster->keywords()->sync(
                $clusterKeywords->pluck('id')->toArray()
            );

            $saved++;
        }

        return $saved;
    }

    /**
     * Detect market gaps: clusters with rising demand and low competition.
     * Returns clusters with at least one keyword where growth >= $growthThreshold
     * and competition score <= $competitionThreshold.
     *
     * @return Collection<DemandCluster>
     */
    public function detectMarketGaps(
        int   $projectId,
        float $growthThreshold      = 20.0,
        float $competitionThreshold = 0.4,
    ): Collection {
        return DemandCluster::where('project_id', $projectId)
            ->where('status', 'active')
            ->with(['keywords.measurements' => fn ($q) => $q->whereNotNull('growth')->latest('date')->limit(1)])
            ->get()
            ->filter(function (DemandCluster $cluster) use ($growthThreshold, $competitionThreshold) {
                return $cluster->keywords->some(function ($keyword) use ($growthThreshold, $competitionThreshold) {
                    $latest = $keyword->measurements->first();
                    if (! $latest) return false;

                    $hasGrowth      = (float) ($latest->growth ?? 0)      >= $growthThreshold;
                    $lowCompetition = (float) ($latest->competition ?? 0)  <= $competitionThreshold;

                    return $hasGrowth && $lowCompetition;
                });
            })
            ->sortByDesc('score')
            ->values();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Group keywords by shared significant root words.
     * Returns array<rootWord, Collection<Keyword>>.
     */
    private function groupByRootWords(Collection $keywords): array
    {
        $groups = [];

        foreach ($keywords as $keyword) {
            $roots = $this->extractRoots($keyword->keyword);

            foreach ($roots as $root) {
                $groups[$root] ??= collect();
                $groups[$root]->push($keyword);
            }
        }

        // Deduplicate keywords within each group
        return array_map(
            fn (Collection $kws) => $kws->unique('id'),
            $groups
        );
    }

    /**
     * Extract significant root words from a keyword string.
     * Words must be ≥ 4 chars and not stopwords.
     *
     * @return string[]
     */
    private function extractRoots(string $keyword): array
    {
        $words = preg_split('/\s+/', strtolower(trim($keyword)));

        return array_values(array_filter(
            $words,
            fn (string $w) => strlen($w) >= 4 && ! in_array($w, self::STOPWORDS, true)
        ));
    }

    private function dominantIntent(Collection $keywords): string
    {
        $counts = $keywords->countBy('intent')->sortDesc();
        return $counts->keys()->first() ?? 'unknown';
    }

    private function clusterName(string $rootWord, Collection $keywords): string
    {
        // Use the most common keyword as the cluster representative name
        $representative = $keywords->sortByDesc(fn ($k) => strlen($k->keyword))->first();
        return Str::title($representative->keyword ?? $rootWord);
    }
}
