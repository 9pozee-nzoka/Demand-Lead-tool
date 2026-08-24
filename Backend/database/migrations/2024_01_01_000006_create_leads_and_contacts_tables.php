<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            // Lead identity
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('location')->nullable();
            $table->string('company')->nullable();
            // Classification
            $table->string('source')->default('unknown'); // landing_page, whatsapp, form, api
            $table->enum('intent', ['informational', 'commercial', 'transactional', 'local', 'unknown'])
                  ->default('unknown');
            $table->decimal('lead_score', 5, 2)->default(0);
            $table->enum('score_label', ['hot', 'warm', 'potential', 'low'])->default('low');
            $table->enum('status', ['new', 'contacted', 'qualified', 'quotation', 'negotiation', 'won', 'lost', 'unqualified'])
                  ->default('new');
            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            // AI qualification summary
            $table->text('qualification_summary')->nullable();
            $table->json('qualification_data')->nullable(); // raw Q&A data
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index('project_id');
            $table->index('lead_score');
            $table->index('status');
        });

        Schema::create('lead_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // viewed_page, form_submitted, whatsapp_sent, called, qualified, assigned
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['lead_id', 'type']);
            $table->index('occurred_at');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('company')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('lead_events');
        Schema::dropIfExists('leads');
    }
};
