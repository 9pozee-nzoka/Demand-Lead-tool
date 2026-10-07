<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            // Add 'term' column (alias for 'keyword')
            $table->string('term')->after('keyword')->nullable();
            
            // Add 'match_type' column
            $table->enum('match_type', ['exact', 'phrase', 'broad'])
                  ->default('phrase')
                  ->after('intent');
            
            // Add 'notes' column
            $table->text('notes')->nullable()->after('status');
        });

        // Copy existing 'keyword' values to 'term' for backward compatibility
        DB::table('keywords')->update(['term' => DB::raw('keyword')]);
    }

    public function down(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            $table->dropColumn(['term', 'match_type', 'notes']);
        });
    }
};
