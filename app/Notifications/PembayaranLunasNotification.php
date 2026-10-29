<?php

namespace App\Notifications;

use App\Models\Periksa;
use Illuminate\Notifications\Notification;

/**
 * Untuk PASIEN: pembayarannya telah diverifikasi (lunas) oleh admin.
 */
class PembayaranLunasNotification extends Notification
{
    public function __construct(public Periksa $periksa)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $biaya = number_format($this->periksa->biaya_periksa, 0, ',', '.');

        return [
            'periksa_id' => $this->periksa->id,
            'pesan' => "Pembayaran Anda sebesar Rp {$biaya} telah diverifikasi dan dinyatakan lunas.",
            'url' => route('pasien.pembayaran.index'),
        ];
    }
}