<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('minimum_score', 5, 2)->default(60);
            $table->decimal('minimum_growth', 8, 2)->default(20);  // % growth threshold
            $table->enum('intent', ['any', 'commercial', 'transactional', 'local'])->default('any');
            $table->string('location')->nullable();
            $table->unsignedInteger('cooldown')->default(24);       // hours between repeat alerts
            $table->json('channels')->nullable();                   // ["sms","email","dashboard"]
            $table->json('recipients')->nullable();                 // phone/email list
            $table->json('quiet_hours')->nullable();                // {"start":"22:00","end":"07:00"}
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->timestamps();

            $table->index('organization_id');
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('alert_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['opportunity', 'lead', 'market_gap', 'competitor', 'system'])->default('opportunity');
            $table->enum('channel', ['sms', 'email', 'whatsapp', 'push', 'dashboard', 'webhook'])->default('dashboard');
            $table->string('recipient')->nullable();    // phone or email
            $table->text('message');
            $table->json('payload')->nullable();        // full alert data
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('organization_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('alert_rules');
    }
};
