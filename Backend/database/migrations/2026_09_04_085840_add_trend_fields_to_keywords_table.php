<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            $table->string('trend_state', 20)->default('unknown')->after('status');
            $table->decimal('baseline_7d', 8, 2)->default(0)->after('trend_state');
            $table->decimal('baseline_30d', 8, 2)->default(0)->after('baseline_7d');
            $table->decimal('baseline_90d', 8, 2)->default(0)->after('baseline_30d');
            $table->decimal('current_interest', 8, 2)->default(0)->after('baseline_90d');
            $table->decimal('growth_rate_7d', 8, 2)->default(0)->after('current_interest');
            $table->decimal('growth_rate_30d', 8, 2)->default(0)->after('growth_rate_7d');
            $table->decimal('growth_rate_90d', 8, 2)->default(0)->after('growth_rate_30d');
            $table->decimal('volatility', 8, 2)->default(0)->after('growth_rate_90d');
            $table->timestamp('last_measured_at')->nullable()->after('volatility');
            $table->timestamp('trend_updated_at')->nullable()->after('last_measured_at');
            
            $table->index('trend_state');
            $table->index('growth_rate_7d');
            $table->index('last_measured_at');
        });
    }

    public function down(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            $table->dropIndex(['trend_state']);
            $table->dropIndex(['growth_rate_7d']);
            $table->dropIndex(['last_measured_at']);
            
            $table->dropColumn([
                'trend_state',
                'baseline_7d',
                'baseline_30d',
                'baseline_90d',
                'current_interest',
                'growth_rate_7d',
                'growth_rate_30d',
                'growth_rate_90d',
                'volatility',
                'last_measured_at',
                'trend_updated_at',
            ]);
        });
    }
};
