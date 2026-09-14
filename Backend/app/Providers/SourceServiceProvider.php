<?php

namespace App\Providers;

use App\Services\Sources\SourceManager;
use Illuminate\Support\ServiceProvider;

class SourceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register base services first so they can be resolved by dependents
        $this->app->singleton(\App\Services\Sources\ParserService::class);

        $this->app->singleton(\App\Services\Sources\NormalizerService::class);

        $this->app->singleton(\App\Services\Sources\ScraperService::class, function ($app) {
            return new \App\Services\Sources\ScraperService(
                $app->make(\App\Services\Sources\ParserService::class),
                $app->make(\App\Services\Sources\NormalizerService::class),
            );
        });

        // SourceManager depends on ScraperService — let the container resolve it
        $this->app->singleton(SourceManager::class, function ($app) {
            return new SourceManager(
                $app->make(\App\Services\Sources\ScraperService::class),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
