<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use Illuminate\Http\Request;

class ObatController extends Controller
{
    public function index()
    {
        $obats = Obat::all();
        return view('admin.obat.index', compact('obats'));
    }

    public function create()
    {
        return view('admin.obat.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_obat' => 'required|string|max:255',
            'kemasan' => 'required|string|max:100',
            'harga' => 'required|numeric|min:0',
        ]);

        Obat::create([
            'nama_obat' => $request->nama_obat,
            'kemasan' => $request->kemasan,
            'harga' => $request->harga,
        ]);

        return redirect()
            ->route('obat.index')
            ->with('message', 'Data Obat berhasil di Tambah>')
            ->with('type', 'success');
    }

    public function edit(Obat $obat)
    {
        return view('admin.obat.edit', compact('obat'));
    }

    public function update(Request $request, Obat $obat)
    {
        $request->validate([
            'nama_obat' => 'required|string|max:255',
            'kemasan' => 'required|string|max:100',
            'harga' => 'required|numeric|min:0',
        ]);

        $obat->update([
            'nama_obat' => $request->nama_obat,
            'kemasan' => $request->kemasan,
            'harga' => $request->harga,
        ]);

        return redirect()
            ->route('obat.index')
            ->with('message', 'Data Obat berhasil di Ubah')
            ->with('type', 'success');
    }

    public function destroy(Obat $obat)
    {
        $obat->delete();
        return redirect()
            ->route('obat.index')
            ->with('message', 'Data Obat berhasil dihapus')
            ->with('type', 'success');
    }
}