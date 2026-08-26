<?php

namespace App\Services;

use App\Models\ServiceHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OperationsHealthService
{
    public function runAll(): array
    {
        return [
            $this->database(),
            $this->ollama(),
            $this->youtubeKey(),
            $this->disk(),
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

    public function ollama(): array
    {
        try {
            $url = rtrim(config('goodtriplove.ollama.base_url'),'/').'/api/tags';
            $r = Http::timeout(3)->get($url);

            return $r->ok()
                ? $this->save('ollama','ok','Ollama reachable')
                : $this->save('ollama','warning','HTTP '.$r->status());
        } catch (\Throwable $e) {
            return $this->save('ollama','down',$e->getMessage());
        }
    }

    public function youtubeKey(): array
    {
        return config('goodtriplove.youtube.api_key')
            ? $this->save('youtube_api','ok','API key configured')
            : $this->save('youtube_api','warning','API key missing');
    }

    public function disk(): array
    {
        $path = base_path();
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if (!$total || $free === false) {
            return $this->save('disk','warning','Unable to read disk usage');
        }

        $usedPercent = round((($total - $free) / $total) * 100, 2);
        $warning = config('growth_ops.monitoring.disk_warning_percent');

        return $this->save(
            'disk',
            $usedPercent >= $warning ? 'warning' : 'ok',
            "Disk used: {$usedPercent}%",
            ['used_percent' => $usedPercent]
        );
    }

    private function save(string $service, string $status, string $message, array $metadata = []): array
    {
        ServiceHealth::create([
            'service' => $service,
            'status' => $status,
            'message' => mb_substr($message,0,2000),
            'metadata' => $metadata,
            'checked_at' => now(),
        ]);

        return compact('service','status','message','metadata');
    }
}
