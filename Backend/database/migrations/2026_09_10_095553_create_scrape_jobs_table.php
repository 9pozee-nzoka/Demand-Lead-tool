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
        Schema::create('scrape_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_scraper_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('items_found')->default(0);
            $table->integer('items_new')->default(0);
            $table->integer('items_updated')->default(0);
            $table->integer('items_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Source-specific job data
            $table->timestamps();

            $table->index(['source_scraper_id', 'status']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrape_jobs');
    }
};
