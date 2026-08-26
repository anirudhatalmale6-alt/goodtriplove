<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('content_notices', function(Blueprint $t){
   $t->id(); $t->foreignId('reporter_user_id')->nullable()->index();
   $t->string('reporter_email')->nullable()->index(); $t->string('target_type',50)->index();
   $t->unsignedBigInteger('target_id')->nullable()->index(); $t->string('target_url',1000)->nullable();
   $t->string('reason',100)->index(); $t->text('explanation'); $t->json('evidence')->nullable();
   $t->string('status',30)->default('received')->index(); $t->string('decision',50)->nullable()->index();
   $t->text('decision_reason')->nullable(); $t->foreignId('reviewed_by')->nullable();
   $t->timestamp('reviewed_at')->nullable(); $t->timestamp('notified_at')->nullable(); $t->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('content_notices'); }
};