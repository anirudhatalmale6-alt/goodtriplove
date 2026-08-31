<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps where a video actually came from.
 *
 * The table already stored `provider` and `provider_video_id`, which is enough
 * to rebuild a YouTube link because YouTube URLs are predictable. Facebook's
 * are not — its embed takes the whole URL rather than an id — and a share link
 * is what an administrator will paste, so the original address has to survive
 * as it was given.
 *
 * Additive only. Nothing is dropped, renamed or rewritten: every existing row
 * keeps its data and simply gains two empty columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('original_url', 500)->nullable()->after('provider_video_id');
            $table->string('author_url', 300)->nullable()->after('channel_title');

            // Not unique: two rows may legitimately hold the same URL for a
            // moment while a duplicate is being resolved, and a unique index
            // would turn that into a 500 instead of a decision.
            $table->index('original_url');
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['provider', 'status']);
            $table->dropIndex(['original_url']);
            $table->dropColumn(['original_url', 'author_url']);
        });
    }
};
