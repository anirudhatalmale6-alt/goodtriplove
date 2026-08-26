<?php
namespace App\Http\Middleware;use Closure;use Illuminate\Http\Request;class EnsureAdmin { public function handle(Request $r,Closure $n){$u=$r->user();if(!$u||!in_array($u->role,['admin','super_admin'],true))abort(403);return $n($r);} }
