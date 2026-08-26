<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class VideoImportWorkflow {
 private array $allowed=[
  'FOUND'=>['FETCHED','REJECTED','FAILED'],'FETCHED'=>['AI_ANALYSIS','FAILED'],
  'AI_ANALYSIS'=>['REVIEW','APPROVED','FAILED'],'REVIEW'=>['APPROVED','REJECTED'],
  'APPROVED'=>['PUBLISHED','REJECTED','FAILED'],'PUBLISHED'=>[],'REJECTED'=>[],'FAILED'=>['FOUND']
 ];
 public function transition(int $id,string $to,?string $note=null): void {
  DB::transaction(function() use($id,$to,$note){
   $row=DB::table('video_imports')->lockForUpdate()->find($id);
   if(!$row) throw new \RuntimeException('Import not found.');
   if(!in_array($to,$this->allowed[$row->status]??[],true)) throw new \RuntimeException('Invalid transition.');
   DB::table('video_imports')->where('id',$id)->update(['status'=>$to,'updated_at'=>now()]);
   DB::table('video_import_transitions')->insert([
    'video_import_id'=>$id,'from_status'=>$row->status,'to_status'=>$to,
    'actor_user_id'=>auth()->id(),'note'=>$note,'created_at'=>now(),'updated_at'=>now()
   ]);
  });
 }
}