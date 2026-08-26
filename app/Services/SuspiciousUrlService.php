<?php
namespace App\Services;
class SuspiciousUrlService { public function isSuspicious(?string $url): bool { if(!$url) return false; if(mb_strlen($url)>2048) return true; $p=@parse_url(trim($url)); if(!$p||empty($p['scheme'])||empty($p['host'])) return true; $s=strtolower($p['scheme']); if(!in_array($s,['http','https'],true)) return true; $h=strtolower($p['host']); if($h==='localhost'||str_ends_with($h,'.local')) return true; if(filter_var($h,FILTER_VALIDATE_IP)&&!filter_var($h,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) return true; return false; } }
