<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL doesn't support ALTER ENUM directly, so we need to use raw SQL
        // or recreate the column
        
        // For MySQL: Use ALTER TABLE ... MODIFY
        DB::statement("ALTER TABLE data_sources MODIFY COLUMN type ENUM(
            'google_trends',
            'google_ads',
            'search_console',
            'customer_analytics',
            'manual',
            'webhook',
            'africas_talking',
            'openai'
        ) NOT NULL DEFAULT 'google_trends'");
        
        // Also update status enum to include 'paused'
        DB::statement("ALTER TABLE data_sources MODIFY COLUMN status ENUM(
            'active',
            'error',
            'disconnected',
            'paused'
        ) NOT NULL DEFAULT 'disconnected'");
    }

    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE data_sources MODIFY COLUMN type ENUM(
            'google_trends',
            'google_ads',
            'search_console',
            'customer_analytics',
            'manual'
        ) NOT NULL DEFAULT 'google_trends'");
        
        DB::statement("ALTER TABLE data_sources MODIFY COLUMN status ENUM(
            'active',
            'error',
            'disconnected'
        ) NOT NULL DEFAULT 'disconnected'");
    }
};
