<?php
namespace App\Services;
use App\Models\SecurityBlock;
class SecurityBlockService { public function isBlocked(string $type,?string $value): bool { return $value?SecurityBlock::where('type',$type)->where('value',$value)->where('blocked_until','>',now())->exists():false; } public function block(string $type,string $value,string $reason): void { SecurityBlock::updateOrCreate(['type'=>$type,'value'=>$value],['reason'=>$reason,'blocked_until'=>now()->addMinutes(config('security.blocking.minutes'))]); } }
