<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The saved searches the Video Collector runs, and a log of every pass.
     *
     * The daily API budget is NOT tracked here: the core-operations module owns
     * that ledger (`youtube_quota_usage`), and having two would guarantee that
     * at least one of them is wrong.
     */
    public function up(): void
    {
        Schema::create('collector_queries', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('query');
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('locale', 5)->nullable();
            $table->string('region_code', 2)->nullable();
            $table->unsignedTinyInteger('max_results')->default(25);
            $table->unsignedInteger('priority')->default(100);   // lower runs first
            $table->unsignedInteger('interval_hours')->default(168);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('runs_count')->default(0);
            $table->unsignedInteger('videos_found')->default(0);
            $table->unsignedInteger('videos_imported')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'priority', 'last_run_at']);
        });

        Schema::create('collector_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collector_query_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('running');  // running|success|failed|skipped
            $table->unsignedInteger('quota_units')->default(0);
            $table->unsignedInteger('items_returned')->default(0);
            $table->unsignedInteger('items_created')->default(0);
            $table->unsignedInteger('items_updated')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collector_runs');
        Schema::dropIfExists('collector_queries');
    }
};
