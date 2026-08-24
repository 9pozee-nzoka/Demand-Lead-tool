<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->json('limits')->nullable();   // {"keywords":25,"users":2,"alerts":50}
            $table->json('features')->nullable();  // list of enabled feature flags
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('industry')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('timezone')->default('UTC');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->string('provider')->default('manual'); // stripe, paddle, manual
            $table->string('external_id')->nullable();
            $table->enum('status', ['active', 'trialing', 'past_due', 'cancelled'])->default('active');
            $table->timestamp('renewal_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('plans');
    }
};
