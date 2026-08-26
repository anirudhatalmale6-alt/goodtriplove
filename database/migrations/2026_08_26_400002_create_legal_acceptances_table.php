<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('legal_acceptances', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->index(); $t->string('document_key',100)->index();
   $t->string('version',40); $t->string('locale',8); $t->string('ip_address',45)->nullable();
   $t->string('user_agent',1000)->nullable(); $t->timestamp('accepted_at')->index(); $t->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('legal_acceptances'); }
};