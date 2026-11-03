<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SafeMailService
{
    /**
     * Kuota harian default untuk mencegah overquota pada penyedia SMTP eksternal gratisan.
     */
    protected int $dailyLimit;

    public function __construct()
    {
        $this->dailyLimit = (int) env('MAIL_DAILY_LIMIT', 100);
    }

    /**
     * Kunci cache harian untuk pelacakan kuota.
     */
    protected function getDailyCacheKey(): string
    {
        return 'smtp_daily_sent_count_'.now()->format('Y_m_d');
    }

    /**
     * Cek status penggunaan kuota email hari ini.
     *
     * @return array{sent_today: int, daily_limit: int, remaining: int, is_limit_reached: bool}
     */
    public function getQuotaStatus(): array
    {
        $sentToday = (int) Cache::get($this->getDailyCacheKey(), 0);
        $remaining = max(0, $this->dailyLimit - $sentToday);

        return [
            'sent_today' => $sentToday,
            'daily_limit' => $this->dailyLimit,
            'remaining' => $remaining,
            'is_limit_reached' => $sentToday >= $this->dailyLimit,
        ];
    }

    /**
     * Mengirim email secara aman dengan penjagaan kuota limit harian API eksternal.
     *
     * @param  string|array<string>  $recipient
     */
    public function sendSafe(string|array $recipient, Mailable $mailable): bool
    {
        $status = $this->getQuotaStatus();

        if ($status['is_limit_reached']) {
            Log::warning(
                "[SafeMailService] Ambang batas limit harian pengiriman email SMTP tercapai ({$status['sent_today']}/{$status['daily_limit']}). ".
                'Email ditahan demi mencegah suspensi akun SMTP eksternal.',
                [
                    'recipient' => $recipient,
                    'subject' => method_exists($mailable, 'envelope') ? $mailable->envelope()->subject : get_class($mailable),
                ]
            );

            return false;
        }

        try {
            Mail::to($recipient)->send($mailable);

            // Inkrementasi hitungan harian dengan masa kedaluwarsa hingga akhir hari
            $secondsUntilEndOfDay = now()->diffInSeconds(now()->endOfDay());
            $key = $this->getDailyCacheKey();

            if (Cache::has($key)) {
                Cache::increment($key);
            } else {
                Cache::put($key, 1, $secondsUntilEndOfDay);
            }

            Log::info("[SafeMailService] Email berhasil terkirim ke: ".json_encode($recipient).". Sisa kuota hari ini: ".($status['remaining'] - 1));

            return true;
        } catch (\Throwable $e) {
            Log::error("[SafeMailService] Kegagalan transmisi SMTP ke ".json_encode($recipient).": ".$e->getMessage());

            return false;
        }
    }
}
