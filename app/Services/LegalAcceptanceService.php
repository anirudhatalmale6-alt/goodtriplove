<?php
namespace App\Services;
use App\Models\LegalAcceptance;
class LegalAcceptanceService {
 public function accept($user,string $key,string $version,string $locale): LegalAcceptance {
  return LegalAcceptance::create([
   'user_id'=>$user->id,'document_key'=>$key,'version'=>$version,'locale'=>$locale,
   'ip_address'=>request()->ip(),'user_agent'=>mb_substr((string)request()->userAgent(),0,1000),
   'accepted_at'=>now()
  ]);
 }
}