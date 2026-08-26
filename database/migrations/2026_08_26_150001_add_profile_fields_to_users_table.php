<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profile columns for GoodTripLove. The security module already adds
     * role / two_factor_* / last_login_*, so every column here is guarded and
     * this migration only fills in what the module does not provide.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 40)->default('user')->index();
            }
            if (! Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 5)->default('fr');
            }
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (! Schema::hasColumn('users', 'country_code')) {
                $table->string('country_code', 2)->nullable();
            }
            if (! Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable();
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 64)->nullable();
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['locale', 'avatar', 'country_code', 'company_name', 'phone', 'is_active'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
