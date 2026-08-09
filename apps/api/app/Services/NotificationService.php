<?php

namespace App\Services;

use App\Models\WaNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private string $fonnteToken;

    private string $fonnteUrl;

    public function __construct()
    {
        $this->fonnteToken = (string) config('services.fonnte.token', '');
        $this->fonnteUrl = (string) config('services.fonnte.url', 'https://api.fonnte.com');
    }

    public function sendWa(
        int $clinicId,
        string $phone,
        string $type,
        string $message,
        string $priority = 'default'
    ): bool {
        // Dedup check: prevent duplicate WA within same day
        $dedupKey = "wa_notif:{$phone}:{$type}:".now()->format('Y-m-d');
        if (Cache::has($dedupKey)) {
            Log::info("WA notification deduplicated: {$type} to {$phone}");

            return true;
        }

        $notification = WaNotification::create([
            'clinic_id' => $clinicId,
            'phone' => $phone,
            'type' => $type,
            'message' => $message,
            'status' => 'pending',
        ]);

        $sent = $this->dispatchToFonnte($phone, $message, $notification->id);

        if ($sent) {
            $notification->update(['status' => 'sent', 'sent_at' => now()]);
            Cache::put($dedupKey, true, 86400);
        }

        return $sent;
    }

    private function dispatchToFonnte(string $phone, string $message, int $notificationId): bool
    {
        if (empty($this->fonnteToken)) {
            Log::warning('Fonnte token not configured. WA not sent.');

            return false;
        }

        $attempts = 0;
        $delays = [5, 15, 45];

        while ($attempts < 3) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['Authorization' => $this->fonnteToken])
                    ->post("{$this->fonnteUrl}/send", [
                        'target' => $phone,
                        'message' => $message,
                    ]);

                if ($response->successful()) {
                    return true;
                }
            } catch (\Exception $e) {
                Log::error("Fonnte error (attempt {$attempts}): ".$e->getMessage());
            }

            $attempts++;
            if ($attempts < 3) {
                sleep($delays[$attempts - 1]);
            }
        }

        WaNotification::find($notificationId)?->update([
            'status' => 'failed',
            'attempts' => 3,
            'error_message' => 'Max retries exceeded',
        ]);

        return false;
    }
}
