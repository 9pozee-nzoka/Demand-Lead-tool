<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('phone', 30)->nullable()->after('email');
            $table->enum('role', ['owner', 'admin', 'analyst', 'marketing', 'sales', 'viewer'])
                  ->default('viewer')->after('phone');
            $table->enum('status', ['active', 'inactive', 'invited'])->default('active')->after('role');
            $table->boolean('two_factor_enabled')->default(false)->after('status');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['phone', 'role', 'status', 'two_factor_enabled', 'two_factor_secret']);
        });
    }
};
