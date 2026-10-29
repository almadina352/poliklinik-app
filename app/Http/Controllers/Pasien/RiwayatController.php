<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use Illuminate\Support\Facades\Auth;

class RiwayatController extends Controller
{
    public function index()
    {
        // Eager loading: total query tetap konstan (4) berapapun jumlah riwayatnya
        $riwayats = DaftarPoli::with(['jadwalPeriksa.dokter.poli', 'periksas'])
            ->where('id_pasien', Auth::id())
            ->latest('created_at')
            ->latest('id')
            ->get();

        return view('pasien.riwayat.index', compact('riwayats'));
    }

    public function show(int $id)
    {
        // Hanya pendaftaran milik pasien login yang sudah diperiksa; selain itu 404
        $daftar = DaftarPoli::with([
                'jadwalPeriksa.dokter.poli',
                'periksas.detailPeriksas.obat',
            ])
            ->where('id_pasien', Auth::id())
            ->whereHas('periksas')
            ->findOrFail($id);

        $periksa = $daftar->periksas->first();

        return view('pasien.riwayat.show', compact('daftar', 'periksa'));
    }
}