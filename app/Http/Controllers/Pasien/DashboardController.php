<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use App\Models\JadwalPeriksa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $urutanHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        // Diurutkan di PHP agar kompatibel dengan SQLite maupun MySQL
        $jadwals = JadwalPeriksa::with('dokter.poli')
            ->get()
            ->sortBy(fn ($j) => array_search($j->hari, $urutanHari) . $j->jam_mulai)
            ->values();

        $data = $this->liveData();

        return view('pasien.dashboard', [
            'jadwals' => $jadwals,
            'antrianAktif' => $data['aktif'],
            'dilayani' => $data['jadwal'],
        ]);
    }

    /**
     * Endpoint JSON data antrian pasien. Dipakai dashboard untuk sinkronisasi awal / setelah siaran Echo diterima.
     */
    public function live()
    {
        return response()->json($this->liveData());
    }

    /**
     * Antrian aktif milik pasien + nomor yang sedang dilayani di tiap jadwal.
     *
     * Antrian "aktif" = pendaftaran yang belum memiliki data pemeriksaan.
     * Nomor yang sedang dilayani pada sebuah jadwal = nomor antrian terkecil
     * yang belum diperiksa pada jadwal tersebut.
     */
    private function liveData(): array
    {
        $dilayani = DaftarPoli::antrianDilayani();

        $aktif = DaftarPoli::with('jadwalPeriksa.dokter.poli')
            ->where('id_pasien', Auth::id())
            ->whereDoesntHave('periksas')
            ->latest('id')
            ->first();

        $aktifData = null;
        if ($aktif) {
            $jadwal = $aktif->jadwalPeriksa;
            $aktifData = [
                'id_jadwal' => $aktif->id_jadwal,
                'poli' => $jadwal->dokter->poli->nama_poli ?? '-',
                'dokter' => $jadwal->dokter->nama ?? '-',
                'jadwal' => sprintf(
                    '%s (%s - %s)',
                    $jadwal->hari,
                    Str::substr($jadwal->jam_mulai, 0, 5),
                    Str::substr($jadwal->jam_selesai, 0, 5)
                ),
                'no_antrian' => $aktif->no_antrian,
                'dilayani' => $dilayani[$aktif->id_jadwal] ?? null,
            ];
        }

        return [
            'aktif' => $aktifData,
            'jadwal' => $dilayani,
        ];
    }
}