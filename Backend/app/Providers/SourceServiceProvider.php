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
        // Register SourceManager as a singleton
        $this->app->singleton(SourceManager::class, function ($app) {
            $manager = new SourceManager();
            
            // Register RSS source provider
            $manager->registerProvider('rss', \App\Services\Sources\Providers\RssSource::class);
            
            // Register Tender source provider
            $manager->registerProvider('tender', \App\Services\Sources\Providers\TenderSource::class);
            
            // Register Webhook source provider
            $manager->registerProvider('webhook', \App\Services\Sources\Providers\WebhookSource::class);
            
            return $manager;
        });

        // Register services as singletons
        $this->app->singleton(\App\Services\Sources\ScraperService::class);
        $this->app->singleton(\App\Services\Sources\ParserService::class);
        $this->app->singleton(\App\Services\Sources\NormalizerService::class);
        $this->app->singleton(\App\Services\Sources\KeywordMatcherService::class);
        $this->app->singleton(\App\Services\Sources\TenderScoringService::class);
        $this->app->singleton(\App\Services\Sources\LeadConversionService::class);
        
        // Register AI services
        $this->app->singleton(\App\Services\AI\OpenAIService::class);
        
        // Register Intelligence services
        $this->app->singleton(\App\Services\Intelligence\IntentClassifier::class);
        $this->app->singleton(\App\Services\Intelligence\EntityExtractor::class);
        $this->app->singleton(\App\Services\Intelligence\OpportunityMatcher::class);
        
        // Register Analytics services
        $this->app->singleton(\App\Services\Analytics\SourceAnalytics::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
