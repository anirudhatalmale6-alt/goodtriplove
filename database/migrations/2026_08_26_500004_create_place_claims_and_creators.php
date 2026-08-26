<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('place_claims', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->index(); $t->unsignedBigInteger('place_id')->index();
   $t->string('business_email'); $t->boolean('email_verified')->default(false);
   $t->string('status',30)->default('pending')->index(); $t->text('proof')->nullable();
   $t->foreignId('reviewed_by')->nullable(); $t->timestamp('reviewed_at')->nullable(); $t->timestamps();
  });
  Schema::create('content_creators', function(Blueprint $t){
   $t->id(); $t->string('platform',40)->index(); $t->string('external_id',255)->index();
   $t->string('name'); $t->string('profile_url',1000)->nullable(); $t->string('channel_url',1000)->nullable();
   $t->json('metadata')->nullable(); $t->timestamps(); $t->unique(['platform','external_id']);
  });
 }
 public function down(): void { Schema::dropIfExists('content_creators'); Schema::dropIfExists('place_claims'); }
};