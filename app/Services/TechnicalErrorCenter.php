<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class TechnicalErrorCenter {
 public function capture(string $source,string $severity,string $message,array $context=[]): void {
  foreach(['password','token','secret','api_key','authorization'] as $k) unset($context[$k]);
  $fp=hash('sha256',$source.'|'.$message);
  $row=DB::table('technical_error_events')->where('fingerprint',$fp)->where('status','open')->first();
  if($row){
   DB::table('technical_error_events')->where('id',$row->id)->update([
    'occurrences'=>DB::raw('occurrences + 1'),'last_seen_at'=>now(),'updated_at'=>now()
   ]);
  } else {
   DB::table('technical_error_events')->insert([
    'source'=>$source,'severity'=>$severity,'fingerprint'=>$fp,'message'=>mb_substr($message,0,5000),
    'context'=>json_encode($context),'occurrences'=>1,'last_seen_at'=>now(),
    'status'=>'open','created_at'=>now(),'updated_at'=>now()
   ]);
  }
 }
}