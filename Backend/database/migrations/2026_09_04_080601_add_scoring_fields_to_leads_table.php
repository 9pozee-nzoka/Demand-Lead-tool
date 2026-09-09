<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->json('score_breakdown')->nullable()->after('lead_score');
            $table->text('score_explanation')->nullable()->after('score_breakdown');
            $table->timestamp('scored_at')->nullable()->after('score_explanation');
            $table->integer('contact_count')->default(0)->after('scored_at');
            $table->timestamp('first_response_at')->nullable()->after('contact_count');
            $table->string('budget_range', 50)->nullable()->after('first_response_at');
            $table->string('company_size', 50)->nullable()->after('budget_range');
            $table->text('message')->nullable()->after('company_size'); // Lead's inquiry message
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'score_breakdown',
                'score_explanation',
                'scored_at',
                'contact_count',
                'first_response_at',
                'budget_range',
                'company_size',
                'message',
            ]);
        });
    }
};
