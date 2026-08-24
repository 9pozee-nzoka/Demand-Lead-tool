<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('default_location')->nullable();
            $table->enum('status', ['active', 'paused', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
        });

        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('keyword');
            $table->string('normalized_keyword');
            $table->string('category')->nullable();
            $table->enum('intent', ['informational', 'commercial', 'transactional', 'local', 'unknown'])
                  ->default('unknown');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['active', 'paused', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('normalized_keyword');
        });

        Schema::create('keyword_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->string('country', 2);
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('county')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->enum('type', ['country', 'region', 'city', 'county'])->default('city');
            $table->timestamps();

            $table->index('keyword_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyword_locations');
        Schema::dropIfExists('keywords');
        Schema::dropIfExists('projects');
    }
};
