<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('youtube_quota_usage', function(Blueprint $t){
   $t->id(); $t->date('usage_date')->unique(); $t->unsignedInteger('units_used')->default(0);
   $t->timestamp('last_request_at')->nullable(); $t->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('youtube_quota_usage'); }
};