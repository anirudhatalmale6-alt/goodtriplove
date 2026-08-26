<?php
namespace App\Http\Middleware;
use Closure;
class MaintenanceGate {
 public function handle($request,Closure $next){
  $enabled=app(\App\Services\FeatureFlagService::class)->enabled('maintenance_mode',false);
  if(!$enabled) return $next($request);
  $user=$request->user();
  if($user && in_array($user->role,config('core_operations.maintenance_bypass_roles'),true)) return $next($request);
  return response()->view('maintenance',[],503);
 }
}