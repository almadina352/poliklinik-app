<?php

namespace App\Notifications;

use App\Models\Periksa;
use Illuminate\Notifications\Notification;

/**
 * Untuk DOKTER: pasien yang ia periksa mengunggah bukti pembayaran.
 */
class PembayaranDiunggahNotification extends Notification
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
        $pasien = $this->periksa->daftarPoli->pasien->nama ?? 'Pasien';

        return [
            'periksa_id' => $this->periksa->id,
            'pesan' => "{$pasien} mengunggah bukti pembayaran pemeriksaan.",
            'url' => route('riwayat-pasien.show', $this->periksa->id),
        ];
    }
}