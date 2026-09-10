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
        Schema::create('source_scrapers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Kenya Business Daily RSS"
            $table->enum('type', ['rss', 'tender', 'webhook', 'api', 'scraper']); // Source provider type
            $table->enum('category', ['news', 'tender', 'lead_capture', 'market_data']); // Data category
            $table->string('base_url')->nullable(); // RSS feed URL, tender site, etc.
            $table->json('configuration')->nullable(); // Source-specific settings (selectors, filters, etc.)
            $table->text('credentials')->nullable(); // Encrypted API keys, auth tokens
            $table->string('schedule')->default('0 */6 * * *'); // Cron expression for scheduled sources
            $table->enum('status', ['active', 'paused', 'error', 'disabled'])->default('active');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('error_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index('next_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_scrapers');
    }
};
