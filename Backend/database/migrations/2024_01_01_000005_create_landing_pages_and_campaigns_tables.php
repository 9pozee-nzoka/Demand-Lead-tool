<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content')->nullable();     // HTML/Blade content
            $table->string('template')->default('default');
            $table->json('meta')->nullable();            // SEO meta, OG tags
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('status');
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('channel', ['google_ads', 'facebook', 'whatsapp', 'email', 'sms', 'organic', 'other'])
                  ->default('other');
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'cancelled'])->default('draft');
            $table->decimal('budget', 12, 2)->nullable();
            $table->string('attribution_key')->unique()->nullable(); // UTM / tracking token
            $table->json('assets')->nullable();                     // links to ad copy, scripts
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('landing_pages');
    }
};
