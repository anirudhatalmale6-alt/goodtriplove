<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('cookie_consents', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->nullable()->index(); $t->string('consent_key',64)->index();
   $t->string('policy_version',40); $t->json('choices'); $t->string('ip_address',45)->nullable();
   $t->timestamp('consented_at')->index(); $t->timestamp('withdrawn_at')->nullable(); $t->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('cookie_consents'); }
};