<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('source_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_scraper_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('scrape_job_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('event_type', [
                'source_created',
                'source_activated',
                'source_paused',
                'source_disabled',
                'job_started',
                'job_completed',
                'job_failed',
                'error_threshold_exceeded',
                'configuration_changed',
                'credentials_updated'
            ]);
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info');
            $table->text('message');
            $table->json('metadata')->nullable(); // Event context data
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Who triggered the event
            $table->timestamps();

            $table->index(['source_scraper_id', 'event_type']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['severity', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_events');
    }
};
