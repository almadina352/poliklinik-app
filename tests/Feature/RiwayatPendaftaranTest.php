<?php

namespace Tests\Feature;

use App\Models\DaftarPoli;
use App\Models\JadwalPeriksa;
use App\Models\Obat;
use App\Models\DetailPeriksa;
use App\Models\Periksa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RiwayatPendaftaranTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create(['nama' => ucfirst($role) . uniqid(), 'email' => uniqid() . '@t.com', 'password' => 'password', 'role' => $role]);
    }

    private function daftar(User $dokter, User $pasien, int $no, bool $diperiksa): DaftarPoli
    {
        $jadwal = JadwalPeriksa::create(['id_dokter' => $dokter->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        $d = DaftarPoli::create(['id_pasien' => $pasien->id, 'id_jadwal' => $jadwal->id, 'keluhan' => 'Demam', 'no_antrian' => $no]);
        if ($diperiksa) {
            $p = Periksa::create(['id_daftar_poli' => $d->id, 'tgl_periksa' => now(), 'catatan' => 'Istirahat cukup', 'biaya_periksa' => 175000]);
            $obat = Obat::create(['nama_obat' => 'Paracetamol', 'kemasan' => 'strip', 'harga' => 5000]);
            DetailPeriksa::create(['id_periksa' => $p->id, 'id_obat' => $obat->id]);
        }

        return $d;
    }

    public function test_riwayat_hanya_milik_pasien_login_dan_menampilkan_status(): void
    {
        $this->withoutVite();
        $dokter = $this->user('dokter');
        $a = $this->user('pasien');
        $b = $this->user('pasien');
        $this->daftar($dokter, $a, 1, true);
        $this->daftar($dokter, $a, 2, false);
        $this->daftar($dokter, $b, 3, true);

        $this->actingAs($a)->get(route('pasien.riwayat.index'))
            ->assertOk()->assertSee('Sudah diperiksa')->assertSee('Menunggu')
            ->assertViewHas('riwayats', fn ($r) => $r->count() === 2);
    }

    public function test_detail_menampilkan_catatan_obat_biaya_dan_terlindungi(): void
    {
        $this->withoutVite();
        $dokter = $this->user('dokter');
        $a = $this->user('pasien');
        $b = $this->user('pasien');
        $sudah = $this->daftar($dokter, $a, 1, true);
        $belum = $this->daftar($dokter, $a, 2, false);

        $this->actingAs($a)->get(route('pasien.riwayat.show', $sudah->id))
            ->assertOk()->assertSee('Istirahat cukup')->assertSee('Paracetamol')->assertSee('175.000');
        $this->actingAs($a)->get(route('pasien.riwayat.show', $belum->id))->assertNotFound();
        $this->actingAs($b)->get(route('pasien.riwayat.show', $sudah->id))->assertNotFound();
    }

    public function test_jumlah_query_riwayat_konstan(): void
    {
        $this->withoutVite();
        $dokter = $this->user('dokter');
        $a = $this->user('pasien');
        $this->actingAs($a);

        $this->daftar($dokter, $a, 1, true);
        DB::flushQueryLog(); DB::enableQueryLog();
        $this->get(route('pasien.riwayat.index'))->assertOk();
        $sedikit = count(DB::getQueryLog());

        foreach (range(2, 8) as $n) { $this->daftar($dokter, $a, $n, true); }
        DB::flushQueryLog();
        $this->get(route('pasien.riwayat.index'))->assertOk();

        $this->assertSame($sedikit, count(DB::getQueryLog()));
    }
}