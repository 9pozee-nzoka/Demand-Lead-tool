<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->foreignId('organization_id')->after('id')->nullable()->constrained()->cascadeOnDelete();
        });

        // Populate organization_id from project
        DB::statement('
            UPDATE landing_pages lp
            INNER JOIN projects p ON lp.project_id = p.id
            SET lp.organization_id = p.organization_id
        ');

        // Make it non-nullable after populating
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
