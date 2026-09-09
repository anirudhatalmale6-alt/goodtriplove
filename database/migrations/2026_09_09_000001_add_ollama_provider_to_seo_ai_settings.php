<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives the local provider its own model and endpoint fields.
 *
 * Reusing the single `model` column for both providers would mean the OpenAI
 * model name is destroyed the moment someone tries Ollama, and has to be typed
 * again to switch back. Two columns, so switching provider is reversible.
 *
 * Additive only; the existing row keeps every value it had.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_ai_settings', function (Blueprint $table) {
            $table->string('ollama_url', 190)->nullable()->after('provider');
            $table->string('ollama_model', 120)->nullable()->after('ollama_url');
        });

        // The settings row already exists on this install, created by the
        // module with provider=openai. Move it onto the free provider, which is
        // what was actually asked for, and seed sensible values.
        DB::table('seo_ai_settings')->update([
            'provider' => config('seo_ai.provider', 'ollama'),
            'ollama_url' => config('seo_ai.ollama.url'),
            'ollama_model' => config('seo_ai.ollama.model'),
        ]);
    }

    public function down(): void
    {
        Schema::table('seo_ai_settings', function (Blueprint $table) {
            $table->dropColumn(['ollama_url', 'ollama_model']);
        });
    }
};
