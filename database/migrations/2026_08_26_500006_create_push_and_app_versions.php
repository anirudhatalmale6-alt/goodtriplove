<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('push_subscriptions', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->nullable()->index(); $t->string('platform',30)->index();
   $t->text('token'); $t->json('preferences')->nullable(); $t->timestamp('last_seen_at')->nullable(); $t->timestamps();
  });
  Schema::create('app_versions', function(Blueprint $t){
   $t->id(); $t->string('platform',20)->index(); $t->string('current_version',40);
   $t->string('minimum_version',40)->nullable(); $t->boolean('force_update')->default(false);
   $t->string('download_url',1000)->nullable(); $t->text('release_notes')->nullable(); $t->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('app_versions'); Schema::dropIfExists('push_subscriptions'); }
};