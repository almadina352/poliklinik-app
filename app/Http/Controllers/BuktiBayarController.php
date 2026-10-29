<?php

namespace App\Http\Controllers;

use App\Models\Periksa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BuktiBayarController extends Controller
{
    /**
     * Menampilkan foto bukti bayar. Hanya untuk admin, pasien pemilik tagihan,
     * atau dokter yang memeriksa. Selain itu 403. Tidak butuh `storage:link`.
     */
    public function show(int $id)
    {
        $periksa = Periksa::with('daftarPoli.jadwalPeriksa')->findOrFail($id);
        $user = Auth::user();

        $boleh = match ($user->role) {
            'admin' => true,
            'pasien' => $periksa->daftarPoli->id_pasien === $user->id,
            'dokter' => $periksa->daftarPoli->jadwalPeriksa->id_dokter === $user->id,
            default => false,
        };

        abort_unless($boleh, 403);
        abort_if(! $periksa->bukti_bayar, 404);

        // Bukti baru disimpan di disk privat (local); fallback ke disk public untuk unggahan lama
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($periksa->bukti_bayar)) {
                return Storage::disk($disk)->response($periksa->bukti_bayar);
            }
        }

        abort(404);
    }
}