<?php

namespace App\Http\Controllers\Pasien;

use App\Events\AntrianDiperbarui;
use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use App\Models\JadwalPeriksa;
use App\Models\Poli;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoliController extends Controller
{
    public function get()
    {
        $user = Auth::user();
        $polis = Poli::all();
        $jadwal = JadwalPeriksa::with('dokter', 'dokter.poli')->get();

        // Antrian yang belum selesai diperiksa (jika ada) -> form dikunci
        $antrianAktif = DaftarPoli::with('jadwalPeriksa.dokter.poli')
            ->where('id_pasien', $user->id)
            ->whereDoesntHave('periksas')
            ->latest('id')
            ->first();

        return view('pasien.daftar', [
            'user' => $user,
            'polis' => $polis,
            'jadwals' => $jadwal,
            'antrianAktif' => $antrianAktif,
        ]);
    }

    public function submit(Request $request)
    {
        $request->validate([
            'id_poli' => 'required|exists:poli,id',
            'id_jadwal' => 'required|exists:jadwal_periksa,id',
            'keluhan' => 'required|string',
        ]);

        $idPasien = Auth::id(); // jangan percaya id dari form

        // Jadwal harus milik dokter pada poli yang dipilih
        $jadwalValid = JadwalPeriksa::where('id', $request->id_jadwal)
            ->whereHas('dokter', fn ($q) => $q->where('id_poli', $request->id_poli))
            ->exists();

        if (! $jadwalValid) {
            return back()
                ->withInput()
                ->with('message', 'Jadwal yang dipilih tidak sesuai dengan poli.')
                ->with('type', 'error');
        }

        $hasil = DB::transaction(function () use ($request, $idPasien) {
            // Ketentuan: pasien tidak boleh mendaftar ke dua poli sekaligus dan
            // baru boleh mendaftar lagi setelah pemeriksaan selesai.
            $sudahAktif = DaftarPoli::where('id_pasien', $idPasien)
                ->whereDoesntHave('periksas')
                ->lockForUpdate()
                ->exists();

            if ($sudahAktif) {
                return false;
            }

            // Kunci jadwal agar nomor antrian tidak bentrok saat daftar bersamaan
            JadwalPeriksa::where('id', $request->id_jadwal)->lockForUpdate()->first();
            $terakhir = DaftarPoli::where('id_jadwal', $request->id_jadwal)->max('no_antrian');

            return DaftarPoli::create([
                'id_pasien' => $idPasien,
                'id_jadwal' => $request->id_jadwal,
                'keluhan' => $request->keluhan,
                'no_antrian' => ($terakhir ?? 0) + 1,
            ]);
        });

        if ($hasil === false) {
            return back()
                ->withInput()
                ->with('message', 'Anda masih memiliki antrian aktif. Pendaftaran baru hanya dapat dilakukan setelah pemeriksaan selesai.')
                ->with('type', 'error');
        }

        AntrianDiperbarui::kirim();

        return redirect()
            ->route('pasien.dashboard')
            ->with('success', 'Berhasil mendaftar ke poli. Nomor antrian Anda: ' . $hasil->no_antrian);
    }
}