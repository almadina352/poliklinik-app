<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\Periksa;
use App\Notifications\PembayaranDiunggahNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    private function milikPasien()
    {
        return Periksa::whereHas('daftarPoli', fn ($q) => $q->where('id_pasien', Auth::id()));
    }

    public function index()
    {
        $periksas = $this->milikPasien()
            ->with('daftarPoli.jadwalPeriksa.dokter.poli')
            ->latest('tgl_periksa')
            ->get();

        return view('pasien.pembayaran.index', compact('periksas'));
    }

    public function store(Request $request, int $id)
    {
        // Selalu kembali ke halaman daftar (bukan back()), agar tidak bergantung pada Referer
        $validator = Validator::make($request->all(), [
            'bukti_bayar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $index = redirect()->route('pasien.pembayaran.index');

        if ($validator->fails()) {
            return $index->withErrors($validator)->with('id_gagal', $id);
        }

        $periksa = $this->milikPasien()->findOrFail($id);

        $path = $request->file('bukti_bayar')->store('bukti-bayar', 'local');

        try {
            // Status & notifikasi satu transaksi: jika notifikasi gagal, status ikut dibatalkan
            $updated = DB::transaction(function () use ($periksa, $path) {
                // Update atomik: hanya berhasil sekali (dari belum_bayar) => notifikasi tidak terkirim ganda
                $updated = Periksa::where('id', $periksa->id)
                    ->where('status_bayar', Periksa::BELUM_BAYAR)
                    ->update(['status_bayar' => Periksa::MENUNGGU, 'bukti_bayar' => $path]);

                if ($updated) {
                    $periksa->refresh()->load('daftarPoli.pasien', 'daftarPoli.jadwalPeriksa.dokter');
                    $periksa->daftarPoli->jadwalPeriksa->dokter->notify(new PembayaranDiunggahNotification($periksa));
                }

                return $updated;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        if (! $updated) {
            Storage::disk('local')->delete($path);

            return $index->with('error', 'Bukti pembayaran sudah pernah diunggah.');
        }

        return $index->with('success', 'Bukti pembayaran berhasil diunggah, menunggu verifikasi admin.');
    }
}