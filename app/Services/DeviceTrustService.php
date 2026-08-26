<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class DeviceTrustService
{
    public function fingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            (string)$request->userAgent(),
            (string)$request->header('accept-language'),
        ]));
    }

    public function register(User $user, Request $request): array
    {
        $hash = $this->fingerprint($request);

        $device = UserDevice::where('user_id',$user->id)
            ->where('device_hash',$hash)
            ->first();

        $isNew = !$device;

        if (!$device) {
            $device = UserDevice::create([
                'user_id' => $user->id,
                'device_hash' => $hash,
                'name' => $this->guessName($request),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string)$request->userAgent(),0,1000),
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        } else {
            $device->update([
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string)$request->userAgent(),0,1000),
                'last_seen_at' => now(),
            ]);
        }

        return [$device, $isNew];
    }

    private function guessName(Request $request): string
    {
        return mb_substr((string)$request->userAgent(), 0, 120);
    }
}
