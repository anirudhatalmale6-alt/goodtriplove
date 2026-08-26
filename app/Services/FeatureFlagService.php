<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class FeatureFlagService {
 public function enabled(string $key,bool $default=false): bool {
  $value=DB::table('feature_flags')->where('key',$key)->value('enabled');
  return $value===null ? $default : (bool)$value;
 }
 public function set(string $key,bool $enabled,array $config=[]): void {
  DB::table('feature_flags')->updateOrInsert(['key'=>$key],[
   'enabled'=>$enabled,'config'=>json_encode($config),'updated_by'=>auth()->id(),
   'updated_at'=>now(),'created_at'=>now()
  ]);
 }
}