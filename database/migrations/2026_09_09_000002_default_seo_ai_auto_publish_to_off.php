<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auto-publication of AI pages is off unless somebody deliberately turns it on.
 *
 * The module shipped with it on. Publishing generated pages without a human
 * reading them is what Google names as scaled content abuse, and the penalty
 * lands on the whole domain — including the place and video pages that carry
 * the site's real value. Off is the only defensible default; the switch is
 * still there in the admin for when the output is trusted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_ai_settings')) {
            return;
        }

        // The column default only matters for a row inserted outside the model,
        // but a default that contradicts the documented behaviour is a trap
        // waiting for the next person.
        //
        // Production is MariaDB; the test suite runs on SQLite, which has no
        // ALTER COLUMN at all and fails the whole migration — and therefore
        // every test — if this is not guarded.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE seo_ai_settings ALTER COLUMN auto_publish SET DEFAULT 0');
        }

        // And the row that already exists on this install.
        DB::table('seo_ai_settings')->update(['auto_publish' => false]);
    }

    public function down(): void
    {
        if (Schema::hasTable('seo_ai_settings')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE seo_ai_settings ALTER COLUMN auto_publish SET DEFAULT 1');
            }
        }
    }
};
