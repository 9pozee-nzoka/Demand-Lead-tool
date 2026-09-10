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
        Schema::create('scraped_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_scraper_id')->constrained()->onDelete('cascade');
            $table->foreignId('scrape_job_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('external_id')->nullable(); // Source's unique identifier
            $table->string('content_hash', 64)->unique(); // SHA-256 hash for deduplication
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->string('url')->nullable();
            $table->string('source_name')->nullable(); // e.g., "Business Daily Africa"
            $table->timestamp('published_at')->nullable();
            
            // Intelligence scoring
            $table->enum('intent', ['informational', 'commercial', 'transactional', 'tender', 'unknown'])->default('unknown');
            $table->decimal('relevance_score', 5, 2)->default(0); // 0-100: keyword match relevance
            $table->decimal('lead_score', 5, 2)->default(0); // 0-100: lead potential
            $table->decimal('opportunity_score', 5, 2)->default(0); // 0-100: opportunity potential
            
            // Processing status
            $table->enum('processing_status', ['pending', 'processed', 'matched', 'converted', 'ignored'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            
            // Relationships
            $table->foreignId('opportunity_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('lead_id')->nullable()->constrained()->onDelete('set null');
            
            // Metadata
            $table->json('matched_keywords')->nullable(); // Array of matched keyword IDs
            $table->json('extracted_entities')->nullable(); // Companies, locations, contacts
            $table->json('metadata')->nullable(); // Source-specific extra data
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'processing_status']);
            $table->index(['source_scraper_id', 'created_at']);
            $table->index('content_hash');
            $table->index(['opportunity_score', 'processing_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraped_items');
    }
};
