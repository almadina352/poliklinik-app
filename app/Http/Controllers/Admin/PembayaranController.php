<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Periksa;
use App\Notifications\PembayaranLunasNotification;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    public function index()
    {
        $periksas = Periksa::with('daftarPoli.pasien', 'daftarPoli.jadwalPeriksa.dokter')
            ->whereIn('status_bayar', [Periksa::MENUNGGU, Periksa::LUNAS])
            ->orderByRaw("status_bayar = 'lunas'")
            ->latest('tgl_periksa')
            ->get();

        return view('admin.pembayaran.index', compact('periksas'));
    }

    public function verifikasi(int $id)
    {
        $periksa = Periksa::findOrFail($id);
        $index = redirect()->route('admin.pembayaran.index');

        // Status & notifikasi satu transaksi: jika notifikasi gagal, status tidak berubah.
        // Update atomik: hanya berhasil sekali (dari menunggu_verifikasi) => notifikasi tidak terkirim ganda.
        $updated = DB::transaction(function () use ($periksa) {
            $updated = Periksa::where('id', $periksa->id)
                ->where('status_bayar', Periksa::MENUNGGU)
                ->update(['status_bayar' => Periksa::LUNAS]);

            if ($updated) {
                $periksa->refresh()->load('daftarPoli.pasien');
                $periksa->daftarPoli->pasien->notify(new PembayaranLunasNotification($periksa));
            }

            return $updated;
        });

        if (! $updated) {
            return $index->with('error', 'Pembayaran ini tidak dapat diverifikasi.');
        }

        return $index->with('success', 'Pembayaran diverifikasi (lunas).');
    }
}