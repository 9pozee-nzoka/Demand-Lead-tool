<?php

namespace App\Services\Demand;

use App\Models\DataSource;
use InvalidArgumentException;

/**
 * Resolves the correct KeywordDataProvider implementation for a given
 * DataSource record. Add new providers here as they are implemented.
 */
class DataProviderFactory
{
    /**
     * Build a provider instance from a DataSource model.
     *
     * @throws InvalidArgumentException when the source type has no provider.
     */
    public function make(DataSource $source): KeywordDataProvider
    {
        return match ($source->type) {
            'google_trends'    => new GoogleTrendsProvider(
                requestDelayMs: (int) config('demand.request_delay_ms', 1200)
            ),
            // Future providers:
            // 'google_ads'     => new GoogleAdsProvider($source->getCredentials()),
            // 'search_console' => new SearchConsoleProvider($source->getCredentials()),
            default => throw new InvalidArgumentException(
                "No provider implemented for source type: {$source->type}"
            ),
        };
    }

    /**
     * Build the default fallback provider (Google Trends public API).
     * Used when an organization has no configured DataSource.
     */
    public function makeDefault(): KeywordDataProvider
    {
        return new GoogleTrendsProvider(
            requestDelayMs: (int) config('demand.request_delay_ms', 1200)
        );
    }
}
