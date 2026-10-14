<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\JadwalPeriksa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JadwalPeriksaController extends Controller
{
    public function index()
    {
        $jadwals = JadwalPeriksa::where('id_dokter', Auth::id())->get();
        return view('dokter.jadwal-periksa.index', compact('jadwals'));
    }

    public function create()
    {
        return view('dokter.jadwal-periksa.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hari'        => 'required|string',
            'jam_mulai'   => 'required',
            'jam_selesai' => 'required',
        ]);

        JadwalPeriksa::create([
            'id_dokter'   => Auth::id(),
            'hari'        => $validated['hari'],
            'jam_mulai'   => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'status'      => $request->status ?? 'aktif',
        ]);

        return redirect()
            ->route('jadwal-periksa.index')
            ->with('message', 'Jadwal Periksa berhasil ditambahkan')
            ->with('type', 'success');
    }

    public function edit(string $id)
    {
        $jadwal = JadwalPeriksa::where('id_dokter', Auth::id())->findOrFail($id);
        return view('dokter.jadwal-periksa.edit', compact('jadwal'));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'hari'        => 'required|string',
            'jam_mulai'   => 'required',
            'jam_selesai' => 'required',
        ]);

        $jadwal = JadwalPeriksa::where('id_dokter', Auth::id())->findOrFail($id);
        
        $jadwal->update([
            'hari'        => $validated['hari'],
            'jam_mulai'   => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'status'      => $request->status ?? $jadwal->status,
        ]);

        return redirect()
            ->route('jadwal-periksa.index')
            ->with('message', 'Jadwal Periksa berhasil diperbarui')
            ->with('type', 'success');
    }

    public function destroy(string $id)
    {
        $jadwal = JadwalPeriksa::where('id_dokter', Auth::id())->findOrFail($id);
        $jadwal->delete();

        return redirect()
            ->route('jadwal-periksa.index')
            ->with('message', 'Jadwal Periksa berhasil dihapus')
            ->with('type', 'success');
    }
}