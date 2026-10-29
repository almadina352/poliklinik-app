<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaftarPoli extends Model
{
    protected $table = 'daftar_poli';

    protected $fillable = [
        'id_pasien',
        'id_jadwal',
        'keluhan',
        'no_antrian'
    ];

    public function pasien()
    {
        return $this->belongsTo(User::class, 'id_pasien');
    }

    public function jadwalPeriksa()
    {
        return $this->belongsTo(JadwalPeriksa::class, 'id_jadwal');
    }

    public function periksas()
    {
        return $this->hasMany(Periksa::class, 'id_daftar_poli');
    }

    /**
     * Nomor antrian yang sedang dilayani per jadwal (id_jadwal => no_antrian).
     * "Sedang dilayani" = nomor terkecil yang belum memiliki data pemeriksaan.
     */
    public static function antrianDilayani(): \Illuminate\Support\Collection
    {
        return static::whereDoesntHave('periksas')
            ->selectRaw('id_jadwal, MIN(no_antrian) as dilayani')
            ->groupBy('id_jadwal')
            ->pluck('dilayani', 'id_jadwal');
    }
}