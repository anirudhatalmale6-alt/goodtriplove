<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class YouTubeQuotaManager {
 public function used(): int {
  return (int)(DB::table('youtube_quota_usage')->where('usage_date',today()->toDateString())->value('units_used') ?? 0);
 }
 public function remaining(): int { return max(0, config('core_operations.youtube.daily_quota')-$this->used()); }
 public function canSpend(int $units): bool {
  $limit=config('core_operations.youtube.daily_quota');
  $hard=$limit*(config('core_operations.youtube.hard_stop_percent')/100);
  return ($this->used()+$units) <= $hard;
 }
 public function spend(int $units): void {
  if(!$this->canSpend($units)) throw new \RuntimeException('YouTube quota safety limit reached.');
  DB::table('youtube_quota_usage')->updateOrInsert(
   ['usage_date'=>today()->toDateString()],
   ['units_used'=>DB::raw('units_used + '.(int)$units),'last_request_at'=>now(),'updated_at'=>now(),'created_at'=>now()]
  );
 }
}