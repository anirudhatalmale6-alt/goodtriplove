<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('legal_documents', function(Blueprint $t){
   $t->id(); $t->string('key',100)->index(); $t->string('locale',8)->index();
   $t->string('version',40); $t->string('title'); $t->longText('content');
   $t->boolean('published')->default(false)->index(); $t->timestamp('published_at')->nullable();
   $t->foreignId('updated_by')->nullable(); $t->timestamps();
   $t->unique(['key','locale','version']);
  });
 }
 public function down(): void { Schema::dropIfExists('legal_documents'); }
};