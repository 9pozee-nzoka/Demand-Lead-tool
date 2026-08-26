<?php

namespace App\Services\Intelligence;

use App\Models\Keyword;

/**
 * Sprint 13 — Classifies search intent for a keyword string.
 *
 * Phase 1 (now): Rule-based classification using signal words.
 * Phase 2 (Sprint 17+): Replace classify() with an OpenAI call that
 * receives the keyword, geo, and recent SERP snippets as context.
 *
 * Intent taxonomy (matches architecture spec §8):
 *   transactional  — ready to buy / hire / book  ("price", "buy", "install", "cost")
 *   local          — nearby service need  ("near me", "in Nairobi", city names)
 *   commercial     — comparing options  ("best", "top", "vs", "review", "compare")
 *   informational  — learning / research  ("what is", "how to", "types of")
 *   unknown        — insufficient signals
 */
class IntentClassificationService
{
    // Signal words ordered by specificity — earlier match wins
    private const TRANSACTIONAL_SIGNALS = [
        'buy', 'price', 'cost', 'quote', 'hire', 'install', 'installation',
        'service', 'services', 'book', 'order', 'purchase', 'cheap', 'affordable',
        'free quote', 'get quote', 'contact', 'repair', 'maintenance',
    ];

    private const LOCAL_SIGNALS = [
        'near me', 'nearby', 'nairobi', 'mombasa', 'kisumu', 'nakuru', 'eldoret',
        'kampala', 'lagos', 'accra', 'johannesburg', 'cape town', 'dar es salaam',
        'in kenya', 'in uganda', 'in nigeria', 'in ghana', 'in south africa',
        'in tanzania', 'local', 'nearest',
    ];

    private const COMMERCIAL_SIGNALS = [
        'best', 'top', 'vs', 'versus', 'compare', 'comparison', 'review',
        'reviews', 'rated', 'rating', 'alternatives', 'providers', 'companies',
        'brands', 'recommended', 'pros and cons', 'worth it',
    ];

    private const INFORMATIONAL_SIGNALS = [
        'what is', 'what are', 'how to', 'how does', 'how do', 'why',
        'types of', 'benefits of', 'advantages', 'disadvantages', 'guide',
        'tutorial', 'explained', 'meaning', 'definition', 'overview',
    ];

    // -------------------------------------------------------------------------

    /**
     * Classify a keyword string and return the intent.
     */
    public function classify(string $keyword): string
    {
        $lower = strtolower(trim($keyword));

        // Check signals in priority order (transactional > local > commercial > informational)
        foreach (self::TRANSACTIONAL_SIGNALS as $signal) {
            if (str_contains($lower, $signal)) {
                return 'transactional';
            }
        }

        foreach (self::LOCAL_SIGNALS as $signal) {
            if (str_contains($lower, $signal)) {
                return 'local';
            }
        }

        foreach (self::COMMERCIAL_SIGNALS as $signal) {
            if (str_contains($lower, $signal)) {
                return 'commercial';
            }
        }

        foreach (self::INFORMATIONAL_SIGNALS as $signal) {
            if (str_contains($lower, $signal)) {
                return 'informational';
            }
        }

        return 'unknown';
    }

    /**
     * Classify and update a Keyword model's intent field.
     * Skips if intent is already explicitly set (non-unknown).
     */
    public function classifyAndSave(Keyword $keyword, bool $force = false): string
    {
        if (! $force && $keyword->intent !== 'unknown') {
            return $keyword->intent;
        }

        $intent = $this->classify($keyword->normalized_keyword ?? $keyword->keyword);

        if ($intent !== $keyword->intent) {
            $keyword->update(['intent' => $intent]);
        }

        return $intent;
    }

    /**
     * Batch-classify all unknown-intent keywords for an organization.
     *
     * @return int Number of keywords updated
     */
    public function batchClassify(int $organizationId): int
    {
        $updated = 0;

        Keyword::whereHas('project', fn ($q) => $q->where('organization_id', $organizationId))
            ->where('intent', 'unknown')
            ->chunkById(100, function ($keywords) use (&$updated) {
                foreach ($keywords as $keyword) {
                    $intent = $this->classify($keyword->normalized_keyword ?? $keyword->keyword);
                    if ($intent !== 'unknown') {
                        $keyword->update(['intent' => $intent]);
                        $updated++;
                    }
                }
            });

        return $updated;
    }
}
