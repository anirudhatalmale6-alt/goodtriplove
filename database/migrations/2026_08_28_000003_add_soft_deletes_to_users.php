<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting an account has to be undoable.
 *
 * A hard delete would take the places a business owns with it, or orphan them,
 * and there is no way back from either. A soft delete gives the admin the
 * three actions asked for — suspend, restore, delete — without a mistake being
 * final. Laravel's user provider honours the global scope, so a deleted account
 * cannot sign in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
