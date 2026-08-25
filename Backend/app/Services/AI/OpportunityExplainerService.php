<?php

namespace App\Services\AI;

use App\Models\Opportunity;
use App\Services\Opportunities\OpportunityScoringService;

/**
 * Generates human-readable explanations and action plans for opportunities.
 *
 * Sprint 7: Rule-based explanations (no API cost, immediate value).
 * Sprint 8+: Replace explain() with an OpenAI/Gemini call, keeping
 *             recommendedActions() rule-based as a reliable fallback.
 */
class OpportunityExplainerService
{
    // -------------------------------------------------------------------------
    // Explanation
    // -------------------------------------------------------------------------

    public function explain(object $opportunity): string
    {
        $keyword  = $opportunity->keyword?->keyword ?? 'this topic';
        $location = $opportunity->location
            ? ($opportunity->location->city ?? $opportunity->location->region ?? $opportunity->location->country)
            : 'your target area';

        $score     = $opportunity->opportunity_score;
        $state     = $opportunity->trend_state;
        $label     = OpportunityScoringService::label($score);
        $intent    = $opportunity->keyword?->intent ?? 'commercial';

        $stateDesc = match ($state) {
            'spike'          => 'is experiencing a sudden spike in searches',
            'rapidly_rising' => 'is rapidly gaining search momentum',
            'rising'         => 'has been steadily rising in search demand',
            'emerging'       => 'is showing early signs of increasing demand',
            'peak'           => 'has reached peak search demand in your area',
            'stable'         => 'shows stable, consistent search demand',
            'declining'      => 'is declining in search demand',
            default          => 'is showing demand activity',
        };

        $intentDesc = match ($intent) {
            'transactional' => 'The intent is strongly transactional — people are ready to buy or hire.',
            'local'         => 'The intent is local and high-urgency — people are searching nearby.',
            'commercial'    => 'The intent is commercial — people are comparing providers.',
            'informational' => 'The intent is informational — people are researching.',
            default         => 'The search intent suggests active interest.',
        };

        return sprintf(
            'Demand for "%s" in %s %s. ' .
            'The opportunity scores %s/100 (%s). ' .
            '%s ' .
            'Acting now puts you ahead of competitors who have not yet responded to this demand signal.',
            $keyword,
            $location,
            $stateDesc,
            number_format($score, 0),
            $label,
            $intentDesc,
        );
    }

    // -------------------------------------------------------------------------
    // Recommended actions
    // -------------------------------------------------------------------------

    /**
     * Returns a prioritised list of recommended actions.
     * Rule-based — always reliable, no API dependency.
     *
     * @return list<string>
     */
    public function recommendedActions(object $opportunity): array
    {
        $score  = $opportunity->opportunity_score;
        $state  = $opportunity->trend_state;
        $intent = $opportunity->keyword?->intent ?? 'unknown';
        $actions = [];

        // Always recommend a landing page for high-intent + high-score
        if ($score >= 60 && in_array($intent, ['transactional', 'local', 'commercial'])) {
            $location = $opportunity->location?->city ?? 'your target location';
            $keyword  = $opportunity->keyword?->keyword ?? 'this topic';
            $actions[] = "Create a landing page targeting \"{$keyword}\" in {$location}.";
        }

        // Alert sales for high scores
        if ($score >= 70) {
            $actions[] = 'Notify sales team about this demand signal.';
        }

        // Campaign for very high scores
        if ($score >= 80) {
            $actions[] = 'Increase campaign visibility for this keyword and location.';
            $actions[] = 'Contact matching prospects in your CRM.';
        }

        // Spike or rapid rise — act urgently
        if (in_array($state, ['spike', 'rapidly_rising'])) {
            $actions[] = 'Act urgently — this demand is spiking and window of advantage is short.';
        }

        // Always recommend monitoring
        $days = $score >= 70 ? 3 : 7;
        $actions[] = "Monitor this opportunity for the next {$days} days.";

        // If declining — acknowledge
        if ($state === 'declining') {
            $actions[] = 'Review existing content and campaigns as demand is declining.';
        }

        return array_values(array_unique($actions));
    }

    // -------------------------------------------------------------------------
    // OpenAI integration (Sprint 8+)
    // -------------------------------------------------------------------------

    /**
     * TODO Sprint 8: Replace rule-based explain() with this method.
     *
     * public function explainWithAI(Opportunity $opportunity): string
     * {
     *     $prompt = $this->buildPrompt($opportunity);
     *     $response = OpenAI::chat()->create([
     *         'model'    => 'gpt-4o-mini',
     *         'messages' => [
     *             ['role' => 'system', 'content' => 'You are a demand intelligence analyst...'],
     *             ['role' => 'user',   'content' => $prompt],
     *         ],
     *         'max_tokens' => 200,
     *     ]);
     *     return $response->choices[0]->message->content;
     * }
     */
}
