<?php
namespace App\Services;
use App\Models\ContentNotice;
class ContentNoticeService {
 public function submit(array $data): ContentNotice {
  return ContentNotice::create(array_merge($data,[
   'reporter_user_id'=>auth()->id(),'status'=>'received'
  ]));
 }
 public function decide(ContentNotice $notice,string $decision,string $reason,int $reviewer): void {
  $notice->update(['status'=>'closed','decision'=>$decision,'decision_reason'=>$reason,
   'reviewed_by'=>$reviewer,'reviewed_at'=>now()]);
 }
}