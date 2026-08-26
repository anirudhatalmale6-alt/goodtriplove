<?php
namespace App\Services;
use App\Models\SubmissionFingerprint;
class DuplicateSubmissionService { public function seenRecently(string $type,array $payload,int $hours=24): bool { $fp=hash('sha256',collect($payload)->map(fn($v)=>is_string($v)?trim(mb_strtolower($v)):$v)->sortKeys()->toJson()); if(SubmissionFingerprint::where('type',$type)->where('fingerprint',$fp)->where('expires_at','>',now())->exists()) return true; SubmissionFingerprint::create(['user_id'=>auth()->id(),'type'=>$type,'fingerprint'=>$fp,'expires_at'=>now()->addHours($hours)]); return false; } }
