<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('security_logs', function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->index();$t->string('event',100)->index();$t->string('severity',20)->default('info')->index();$t->string('ip_address',45)->nullable()->index();$t->string('user_agent',1000)->nullable();$t->boolean('success')->default(true)->index();$t->json('metadata')->nullable();$t->timestamps();});
  Schema::create('security_blocks', function(Blueprint $t){$t->id();$t->string('type',20);$t->string('value',255)->index();$t->string('reason')->nullable();$t->timestamp('blocked_until')->index();$t->timestamps();$t->unique(['type','value']);});
  Schema::create('email_verification_codes', function(Blueprint $t){$t->id();$t->foreignId('user_id')->index();$t->string('code_hash');$t->unsignedTinyInteger('attempts')->default(0);$t->timestamp('expires_at')->index();$t->timestamp('verified_at')->nullable();$t->timestamps();});
  Schema::create('submission_fingerprints', function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->index();$t->string('type',60)->index();$t->string('fingerprint',64)->index();$t->timestamp('expires_at')->index();$t->timestamps();$t->unique(['type','fingerprint']);});
 }
 public function down(): void {Schema::dropIfExists('submission_fingerprints');Schema::dropIfExists('email_verification_codes');Schema::dropIfExists('security_blocks');Schema::dropIfExists('security_logs');}
};
