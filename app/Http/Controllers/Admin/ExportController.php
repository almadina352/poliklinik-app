<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\User;
use App\Support\XlsxExport;

class ExportController extends Controller
{
    public function dokter()
    {
        $rows = User::where('role', 'dokter')->with('poli')->orderBy('nama')->get()
            ->map(fn ($d, $i) => [
                $i + 1, $d->nama, $d->email, $d->poli->nama_poli ?? '-',
                $d->no_ktp, $d->no_hp, $d->alamat,
            ]);

        return XlsxExport::download(
            'data-dokter-' . now()->format('Ymd') . '.xlsx', 'Data Dokter',
            ['No', 'Nama Dokter', 'Email', 'Poli', 'No. KTP', 'No. HP', 'Alamat'],
            $rows
        );
    }

    public function pasien()
    {
        $rows = User::where('role', 'pasien')->orderBy('nama')->get()
            ->map(fn ($p, $i) => [
                $i + 1, $p->no_rm, $p->nama, $p->email, $p->no_ktp, $p->no_hp, $p->alamat,
            ]);

        return XlsxExport::download(
            'data-pasien-' . now()->format('Ymd') . '.xlsx', 'Data Pasien',
            ['No', 'No. RM', 'Nama Pasien', 'Email', 'No. KTP', 'No. HP', 'Alamat'],
            $rows
        );
    }

    public function obat()
    {
        $rows = Obat::orderBy('nama_obat')->get()
            ->map(fn ($o, $i) => [
                $i + 1, $o->nama_obat, $o->kemasan, (int) $o->stok, (int) $o->harga,
            ]);

        return XlsxExport::download(
            'data-obat-' . now()->format('Ymd') . '.xlsx', 'Data Obat',
            ['No', 'Nama Obat', 'Kemasan', 'Stok', 'Harga (Rp)'],
            $rows
        );
    }
}