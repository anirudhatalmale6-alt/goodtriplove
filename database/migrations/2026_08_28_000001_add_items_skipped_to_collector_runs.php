<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many results the relevance gate rejected. Without it a run that threw
 * away 20 of 25 results is indistinguishable from one that found only 5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collector_runs', function (Blueprint $table) {
            $table->unsignedInteger('items_skipped')->default(0)->after('items_created');
        });
    }

    public function down(): void
    {
        Schema::table('collector_runs', function (Blueprint $table) {
            $table->dropColumn('items_skipped');
        });
    }
};
