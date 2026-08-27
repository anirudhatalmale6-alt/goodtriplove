<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remember which saved search imported each video.
 *
 * The classifier treats "the video's own text agrees with the search that found
 * it" as its strongest signal, but that context existed only in memory during
 * the import. Re-running classification later therefore lost it, and no video
 * could ever reach the confident band again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('collector_query_id')->nullable()->after('source')
                ->constrained('collector_queries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collector_query_id');
        });
    }
};
