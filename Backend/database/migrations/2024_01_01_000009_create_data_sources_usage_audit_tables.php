<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['google_trends', 'google_ads', 'search_console', 'customer_analytics', 'manual'])
                  ->default('google_trends');
            $table->text('encrypted_credentials')->nullable(); // AES-256 encrypted JSON
            $table->enum('status', ['active', 'error', 'disconnected'])->default('disconnected');
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_meta')->nullable();             // last error, record count, etc.
            $table->timestamps();

            $table->index('organization_id');
        });

        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('metric');          // keywords, api_requests, ai_requests, alerts, leads
            $table->unsignedBigInteger('quantity')->default(0);
            $table->string('period');          // YYYY-MM (monthly billing period)
            $table->timestamps();

            $table->unique(['organization_id', 'metric', 'period']);
            $table->index('organization_id');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');          // created, updated, deleted, login, etc.
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();  // before/after values, IP, user-agent
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('organization_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('data_sources');
    }
};
