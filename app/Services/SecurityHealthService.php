<?php

namespace App\Services;

use App\Models\SecurityHealthCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SecurityHealthService
{
    public function runAll(): array
    {
        return [
            $this->database(),
            $this->mail(),
            $this->ollama(),
            $this->youtube(),
        ];
    }

    public function database(): array
    {
        try {
            DB::select('SELECT 1');
            return $this->save('database','ok','Database reachable');
        } catch (\Throwable $e) {
            return $this->save('database','down',$e->getMessage());
        }
    }

    public function mail(): array
    {
        try {
            $mailer = config('mail.default');
            return $this->save('mail','ok',"Mailer configured: {$mailer}");
        } catch (\Throwable $e) {
            return $this->save('mail','warning',$e->getMessage());
        }
    }

    public function ollama(): array
    {
        try {
            $url = rtrim(config('security_center.ollama.base_url'),'/').'/api/tags';
            $r = Http::timeout(3)->get($url);

            return $r->ok()
                ? $this->save('ollama','ok','Ollama reachable')
                : $this->save('ollama','warning','Ollama returned HTTP '.$r->status());
        } catch (\Throwable $e) {
            return $this->save('ollama','down',$e->getMessage());
        }
    }

    public function youtube(): array
    {
        // config(), not env(): env() returns null once config is cached in production.
        $key = config('goodtriplove.youtube.api_key');

        if (!$key) {
            return $this->save('youtube_api','warning','YouTube API key not configured');
        }

        return $this->save('youtube_api','ok','YouTube API key configured');
    }

    private function save(string $service, string $status, string $message): array
    {
        SecurityHealthCheck::create([
            'service' => $service,
            'status' => $status,
            'message' => mb_substr($message,0,2000),
            'checked_at' => now(),
        ]);

        return compact('service','status','message');
    }
}
