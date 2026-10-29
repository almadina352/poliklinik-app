<?php

namespace App\Events;

use App\Models\DaftarPoli;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Disiarkan setiap kali nomor antrian yang sedang dilayani berubah
 * (pasien mendaftar atau pasien selesai diperiksa).
 *
 * Channel publik "antrian" hanya membawa nomor antrian per jadwal,
 * tidak ada data pribadi pasien.
 */
class AntrianDiperbarui implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function broadcastOn(): array
    {
        return [new Channel('antrian')];
    }

    public function broadcastAs(): string
    {
        return 'antrian.diperbarui';
    }

    public function broadcastWith(): array
    {
        return ['dilayani' => DaftarPoli::antrianDilayani()];
    }

    /**
     * Kirim siaran tanpa pernah menggagalkan proses utama
     * (mis. server Reverb sedang mati) - kegagalan hanya dicatat di log.
     */
    public static function kirim(): void
    {
        try {
            static::dispatch();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}