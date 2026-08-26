<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('ai_corrections', function(Blueprint $t){
   $t->id(); $t->string('entity_type',50)->index(); $t->unsignedBigInteger('entity_id')->nullable()->index();
   $t->string('model',100)->nullable(); $t->decimal('confidence',5,2)->nullable();
   $t->json('original_prediction')->nullable(); $t->json('corrected_values');
   $t->foreignId('corrected_by')->index(); $t->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('ai_corrections'); }
};