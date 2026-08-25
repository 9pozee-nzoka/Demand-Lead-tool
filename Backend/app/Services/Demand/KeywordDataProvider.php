<?php

namespace App\Services\Demand;

/**
 * Contract that every keyword data provider must implement.
 * All providers return normalised data — raw source structures
 * are never exposed outside the provider class.
 */
interface KeywordDataProvider
{
    /**
     * Return daily interest values (0-100) for the keyword in the
     * given location over the requested period.
     *
     * @param  string  $keyword   Normalised keyword string
     * @param  string  $geo       ISO-3166 country code, e.g. "KE"
     * @param  string  $period    'today 3-m', 'today 12-m', etc.
     * @return array<array{date: string, interest: int, geo: string}>
     */
    public function getInterest(string $keyword, string $geo, string $period): array;

    /**
     * Return related rising queries for a keyword.
     *
     * @return array<array{query: string, value: int|string}>
     */
    public function getRelatedQueries(string $keyword, string $geo): array;

    /**
     * Return an estimated monthly search volume (where available).
     * Providers that cannot supply this should return null.
     */
    public function getSearchVolume(string $keyword, string $geo): ?int;

    /**
     * Human-readable name for this provider, used in records.
     */
    public function getName(): string;
}
