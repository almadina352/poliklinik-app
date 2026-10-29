<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\JadwalPeriksa;
use App\Models\Periksa;
use App\Support\XlsxExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    /** Hanya jadwal milik dokter yang sedang login. */
    public function jadwal()
    {
        $rows = JadwalPeriksa::where('id_dokter', Auth::id())->orderBy('hari')->get()
            ->map(fn ($j, $i) => [
                $i + 1, $j->hari, Str::substr($j->jam_mulai, 0, 5), Str::substr($j->jam_selesai, 0, 5),
            ]);

        return XlsxExport::download(
            'jadwal-periksa-' . now()->format('Ymd') . '.xlsx', 'Jadwal Periksa',
            ['No', 'Hari', 'Jam Mulai', 'Jam Selesai'],
            $rows
        );
    }

    /** Hanya pasien yang pernah diperiksa dokter yang sedang login. */
    public function riwayat()
    {
        $rows = Periksa::whereHas('daftarPoli.jadwalPeriksa', fn ($q) => $q->where('id_dokter', Auth::id()))
            ->with(['daftarPoli.pasien', 'detailPeriksas.obat'])
            ->orderBy('tgl_periksa', 'desc')
            ->get()
            ->map(fn ($p, $i) => [
                $i + 1,
                \Carbon\Carbon::parse($p->tgl_periksa)->format('d/m/Y H:i'),
                $p->daftarPoli->no_antrian,
                $p->daftarPoli->pasien->nama ?? '-',
                $p->daftarPoli->keluhan,
                $p->catatan,
                $p->detailPeriksas->map(fn ($d) => $d->obat->nama_obat ?? null)->filter()->implode(', '),
                (int) $p->biaya_periksa,
            ]);

        return XlsxExport::download(
            'riwayat-pasien-' . now()->format('Ymd') . '.xlsx', 'Riwayat Pasien',
            ['No', 'Tanggal Periksa', 'No. Antrian', 'Nama Pasien', 'Keluhan', 'Catatan Dokter', 'Obat', 'Biaya (Rp)'],
            $rows
        );
    }
}