<?php

namespace Tests\Feature;

use App\Models\DaftarPoli;
use App\Models\DetailPeriksa;
use App\Models\JadwalPeriksa;
use App\Models\Obat;
use App\Models\Periksa;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ExportExcelTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?int $poli = null, string $nama = null): User
    {
        return User::create(['nama' => $nama ?? ucfirst($role) . uniqid(), 'email' => uniqid() . '@t.com', 'password' => 'password', 'role' => $role, 'id_poli' => $poli]);
    }

    /** Ambil isi sheet1.xml dari response unduhan. */
    private function sheetXml($response): string
    {
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        $file = $response->baseResponse->getFile()->getPathname();
        $copy = tempnam(sys_get_temp_dir(), 'x');
        copy($file, $copy);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($copy));
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (($keep = getenv('KEEP_XLSX')) && ($name = $this->name())) {
            copy($copy, $keep . '/' . $name . '.xlsx');
        }
        unlink($copy);

        return $xml;
    }

    public function test_admin_export_dokter_pasien_obat(): void
    {
        $admin = $this->user('admin');
        $poli = Poli::create(['nama_poli' => 'Gigi']);
        $this->user('dokter', $poli->id, 'dr. Budi & <Co>');
        $this->user('pasien', null, 'Siti Aminah');
        Obat::create(['nama_obat' => 'Amoxicillin', 'kemasan' => 'Strip', 'stok' => 40, 'harga' => 12500]);

        $x = $this->sheetXml($this->actingAs($admin)->get(route('export.dokter')));
        $this->assertStringContainsString('Nama Dokter', $x);
        $this->assertStringContainsString('dr. Budi &amp; &lt;Co&gt;', $x); // ter-escape
        $this->assertStringContainsString('Gigi', $x);
        $this->assertStringNotContainsString('Siti Aminah', $x);

        $x = $this->sheetXml($this->actingAs($admin)->get(route('export.pasien')));
        $this->assertStringContainsString('Siti Aminah', $x);
        $this->assertStringNotContainsString('Budi', $x);

        $x = $this->sheetXml($this->actingAs($admin)->get(route('export.obat')));
        foreach (['Stok', 'Harga (Rp)', 'Amoxicillin'] as $t) {
            $this->assertStringContainsString($t, $x);
        }
        $this->assertStringContainsString('<v>40</v>', $x);
        $this->assertStringContainsString('<v>12500</v>', $x);
    }

    public function test_dokter_hanya_mengekspor_data_miliknya(): void
    {
        $a = $this->user('dokter');
        $b = $this->user('dokter');
        JadwalPeriksa::create(['id_dokter' => $a->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '10:00']);
        JadwalPeriksa::create(['id_dokter' => $b->id, 'hari' => 'Jumat', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00']);

        $x = $this->sheetXml($this->actingAs($a)->get(route('export.jadwal')));
        $this->assertStringContainsString('Senin', $x);
        $this->assertStringContainsString('08:00', $x);
        $this->assertStringNotContainsString('Jumat', $x);

        // Riwayat: pasien A diperiksa dokter A, pasien B diperiksa dokter B
        $obat = Obat::create(['nama_obat' => 'Paracetamol', 'kemasan' => 'Strip', 'stok' => 5, 'harga' => 5000]);
        foreach ([[$a, 'Pasien Milik A'], [$b, 'Pasien Milik B']] as [$dokter, $nama]) {
            $j = JadwalPeriksa::where('id_dokter', $dokter->id)->first();
            $d = DaftarPoli::create(['id_pasien' => $this->user('pasien', null, $nama)->id, 'id_jadwal' => $j->id, 'keluhan' => 'Demam', 'no_antrian' => 1]);
            $p = Periksa::create(['id_daftar_poli' => $d->id, 'tgl_periksa' => now(), 'catatan' => 'Istirahat', 'biaya_periksa' => 155000]);
            DetailPeriksa::create(['id_periksa' => $p->id, 'id_obat' => $obat->id]);
        }

        $x = $this->sheetXml($this->actingAs($a)->get(route('export.riwayat')));
        $this->assertStringContainsString('Pasien Milik A', $x);
        $this->assertStringContainsString('Paracetamol', $x);
        $this->assertStringContainsString('<v>155000</v>', $x);
        $this->assertStringNotContainsString('Pasien Milik B', $x);
    }

    public function test_akses_dibatasi_berdasarkan_role(): void
    {
        $pasien = $this->user('pasien');
        $dokter = $this->user('dokter');
        $admin = $this->user('admin');

        $this->actingAs($pasien)->get(route('export.dokter'))->assertForbidden();
        $this->actingAs($dokter)->get(route('export.obat'))->assertForbidden();
        $this->actingAs($admin)->get(route('export.jadwal'))->assertForbidden();
        $this->actingAs($pasien)->get(route('export.riwayat'))->assertForbidden();
        auth()->logout();
        $this->get(route('export.pasien'))->assertRedirect();
    }

    public function test_form_obat_menyimpan_stok_dan_tombol_export_tampil(): void
    {
        $this->withoutVite();
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('obat.store'), ['nama_obat' => 'Vit C', 'kemasan' => 'Botol', 'harga' => 8000, 'stok' => 25]);
        $this->assertSame(25, Obat::first()->stok);

        $this->actingAs($admin)->post(route('obat.store'), ['nama_obat' => 'X', 'kemasan' => 'Y', 'harga' => 1, 'stok' => -1])
            ->assertSessionHasErrors('stok');

        $this->actingAs($admin)->get(route('obat.index'))->assertOk()
            ->assertSee('Export Excel')->assertSee('Stok');
    }
}