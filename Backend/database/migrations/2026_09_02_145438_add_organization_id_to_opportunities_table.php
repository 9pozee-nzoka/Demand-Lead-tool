<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add organization_id column
        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->index('organization_id');
        });

        // Backfill organization_id from projects
        DB::statement('
            UPDATE opportunities o
            INNER JOIN projects p ON o.project_id = p.id
            SET o.organization_id = p.organization_id
        ');

        // Make it non-nullable after backfill
        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
