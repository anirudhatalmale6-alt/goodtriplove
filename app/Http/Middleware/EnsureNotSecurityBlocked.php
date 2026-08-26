<?php
namespace App\Http\Middleware;
use App\Services\SecurityBlockService;use Closure;use Illuminate\Http\Request;
class EnsureNotSecurityBlocked { public function __construct(private SecurityBlockService $b){} public function handle(Request $r,Closure $n){$acct=auth()->check()?(string)auth()->id():mb_strtolower((string)$r->input('email'));if($this->b->isBlocked('ip',$r->ip())||$this->b->isBlocked('account',$acct))abort(429,'Temporarily blocked for security reasons.');return $n($r);} }
