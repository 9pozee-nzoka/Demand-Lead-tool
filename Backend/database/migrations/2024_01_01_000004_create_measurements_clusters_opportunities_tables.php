<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keyword_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->string('source');           // google_trends, google_ads, search_console
            $table->date('date');
            $table->unsignedTinyInteger('interest')->default(0); // 0-100 Google Trends style
            $table->unsignedBigInteger('volume')->nullable();    // search volume
            $table->decimal('growth', 8, 2)->nullable();         // % vs baseline
            $table->decimal('competition', 5, 4)->nullable();    // 0-1
            $table->decimal('cpc', 8, 2)->nullable();
            $table->string('geo')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['keyword_id', 'date']);
            $table->index(['keyword_id', 'source', 'date']);
        });

        Schema::create('demand_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->enum('intent', ['informational', 'commercial', 'transactional', 'local', 'mixed'])
                  ->default('mixed');
            $table->decimal('score', 5, 2)->default(0);
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();

            $table->index('project_id');
        });

        Schema::create('cluster_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained('demand_clusters')->cascadeOnDelete();
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->decimal('relevance_score', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['cluster_id', 'keyword_id']);
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('keyword_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cluster_id')->nullable()->constrained('demand_clusters')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('keyword_locations')->nullOnDelete();
            // Component scores
            $table->decimal('growth_score', 5, 2)->default(0);
            $table->decimal('intent_score', 5, 2)->default(0);
            $table->decimal('geo_score', 5, 2)->default(0);
            $table->decimal('volume_score', 5, 2)->default(0);
            $table->decimal('competition_score', 5, 2)->default(0);
            $table->decimal('historical_score', 5, 2)->default(0);
            $table->decimal('opportunity_score', 5, 2)->default(0);
            // Metadata
            $table->string('title')->nullable();
            $table->text('explanation')->nullable();      // AI-generated why this matters
            $table->json('recommended_actions')->nullable();
            $table->enum('trend_state', ['emerging', 'rising', 'rapidly_rising', 'spike', 'peak', 'stable', 'declining', 'normal'])
                  ->default('normal');
            $table->enum('status', ['detected', 'reviewed', 'actioned', 'converting', 'won', 'dismissed', 'expired'])
                  ->default('detected');
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('opportunity_score');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('cluster_keywords');
        Schema::dropIfExists('demand_clusters');
        Schema::dropIfExists('keyword_measurements');
    }
};
