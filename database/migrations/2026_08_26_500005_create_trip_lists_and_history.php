<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('trip_lists', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->index(); $t->string('name'); $t->boolean('is_public')->default(false);
   $t->string('slug')->nullable()->index(); $t->timestamps();
  });
  Schema::create('trip_list_items', function(Blueprint $t){
   $t->id(); $t->foreignId('trip_list_id')->index(); $t->string('entity_type',30)->index();
   $t->unsignedBigInteger('entity_id')->index(); $t->unsignedInteger('position')->default(0); $t->timestamps();
   $t->unique(['trip_list_id','entity_type','entity_id']);
  });
  Schema::create('user_view_history', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->index(); $t->string('entity_type',30)->index();
   $t->unsignedBigInteger('entity_id')->index(); $t->timestamp('viewed_at')->index(); $t->timestamps();
  });
 }
 public function down(): void {
  Schema::dropIfExists('user_view_history'); Schema::dropIfExists('trip_list_items'); Schema::dropIfExists('trip_lists');
 }
};