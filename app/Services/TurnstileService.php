<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
class TurnstileService { public function verify(?string $token,?string $ip=null): bool { if(!$token||!config('security.turnstile.secret_key')) return false; $r=Http::asForm()->timeout(5)->retry(2,150)->post(config('security.turnstile.verify_url'),['secret'=>config('security.turnstile.secret_key'),'response'=>$token,'remoteip'=>$ip]); return $r->ok()&&(bool)data_get($r->json(),'success',false); } }
