<?php
namespace App\Services;
use App\Models\SecurityLog;
class SecurityLogger { public function log(string $event,bool $success=true,string $severity='info',array $metadata=[]): void { SecurityLog::create(['user_id'=>auth()->id(),'event'=>$event,'severity'=>$severity,'ip_address'=>request()->ip(),'user_agent'=>mb_substr((string)request()->userAgent(),0,1000),'success'=>$success,'metadata'=>$metadata]); } }
