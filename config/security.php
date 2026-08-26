<?php
return [
 'turnstile'=>[
  'site_key'=>env('TURNSTILE_SITE_KEY'),
  'secret_key'=>env('TURNSTILE_SECRET_KEY'),
  'verify_url'=>env('TURNSTILE_VERIFY_URL','https://challenges.cloudflare.com/turnstile/v0/siteverify'),
 ],
 'alerts'=>['admin_email'=>env('SECURITY_ADMIN_ALERT_EMAIL')],
 'blocking'=>['minutes'=>(int)env('SECURITY_BLOCK_MINUTES',30)],
 'login'=>['max_attempts'=>(int)env('SECURITY_LOGIN_MAX_ATTEMPTS',5)],
 'email_code'=>['ttl_minutes'=>(int)env('SECURITY_EMAIL_CODE_TTL_MINUTES',10),'max_attempts'=>(int)env('SECURITY_EMAIL_CODE_MAX_ATTEMPTS',5)],
 'logs'=>['retention_days'=>(int)env('SECURITY_LOG_RETENTION_DAYS',180)],
 'uploads'=>['max_kb'=>5120,'allowed_mimes'=>['image/jpeg','image/png','image/webp','application/pdf'],'allowed_extensions'=>['jpg','jpeg','png','webp','pdf']],
];
