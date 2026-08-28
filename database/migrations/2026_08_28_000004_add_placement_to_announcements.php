<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where an announcement is shown, and on which pages.
 *
 * Everything went into the scrolling bar at the top, on every page, with no way
 * to say otherwise. Existing rows keep exactly that behaviour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('placement', 16)->default('ticker')->after('emoji');
            $table->boolean('home_only')->default(false)->after('placement');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['placement', 'home_only']);
        });
    }
};
