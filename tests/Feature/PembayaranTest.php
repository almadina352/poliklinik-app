<?php

namespace Tests\Feature;

use App\Models\DaftarPoli;
use App\Models\JadwalPeriksa;
use App\Models\Periksa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PembayaranTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create(['nama' => $role . uniqid(), 'email' => uniqid() . '@t.com', 'password' => 'password', 'role' => $role]);
    }

    public function test_alur_lengkap_tagihan_upload_verifikasi_lunas(): void
    {
        $this->withoutVite();
        Storage::fake('local');
        $dokter = $this->user('dokter');
        $pasien = $this->user('pasien');
        $admin = $this->user('admin');
        $j = JadwalPeriksa::create(['id_dokter' => $dokter->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        $d = DaftarPoli::create(['id_pasien' => $pasien->id, 'id_jadwal' => $j->id, 'keluhan' => 'x', 'no_antrian' => 1]);
        $p = Periksa::create(['id_daftar_poli' => $d->id, 'tgl_periksa' => now(), 'biaya_periksa' => 200000]);

        // Pasien melihat tagihan (belum dibayar) & menu Pembayaran di sidebar
        $this->actingAs($pasien)->get(route('pasien.pembayaran.index'))
            ->assertOk()->assertSee('200.000')->assertSee('Belum dibayar')->assertSee('Pembayaran');

        // Belum upload -> admin belum melihat tagihan ini
        $this->actingAs($admin)->get(route('admin.pembayaran.index'))->assertOk()->assertDontSee('200.000');

        // Upload bukti (foto)
        $this->actingAs($pasien)->post(route('pasien.pembayaran.store', $p->id), ['bukti_bayar' => UploadedFile::fake()->image('bukti.png')]);
        Storage::disk('local')->assertExists($p->fresh()->bukti_bayar);

        // Admin melihat daftar menunggu verifikasi + bisa melihat bukti
        $this->actingAs($admin)->get(route('admin.pembayaran.index'))
            ->assertOk()->assertSee('Menunggu verifikasi')->assertSee('Verifikasi (Lunas)')->assertSee('1')->assertSee('Lihat bukti');
        $this->actingAs($admin)->get(route('pembayaran.bukti', $p->id))->assertOk();

        // Admin konfirmasi -> lunas
        $this->actingAs($admin)->post(route('admin.pembayaran.verifikasi', $p->id));
        $this->assertSame('lunas', $p->fresh()->status_bayar);
        $this->actingAs($pasien)->get(route('pasien.pembayaran.index'))->assertSee('Lunas');
    }

    public function test_bukti_hanya_bisa_dilihat_pihak_terkait_dan_upload_divalidasi(): void
    {
        Storage::fake('local');
        $dokter = $this->user('dokter');
        $pasien = $this->user('pasien');
        $lain = $this->user('pasien');
        $dokterLain = $this->user('dokter');
        $j = JadwalPeriksa::create(['id_dokter' => $dokter->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        $d = DaftarPoli::create(['id_pasien' => $pasien->id, 'id_jadwal' => $j->id, 'keluhan' => 'x', 'no_antrian' => 1]);
        $p = Periksa::create(['id_daftar_poli' => $d->id, 'tgl_periksa' => now(), 'biaya_periksa' => 1000]);

        // File bukan gambar ditolak; pasien lain tidak bisa mengunggah untuk tagihan ini
        $this->actingAs($pasien)->post(route('pasien.pembayaran.store', $p->id), ['bukti_bayar' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('bukti_bayar');
        $this->actingAs($lain)->post(route('pasien.pembayaran.store', $p->id), ['bukti_bayar' => UploadedFile::fake()->image('a.jpg')])->assertNotFound();

        $this->actingAs($pasien)->post(route('pasien.pembayaran.store', $p->id), ['bukti_bayar' => UploadedFile::fake()->image('a.jpg')]);

        $this->actingAs($pasien)->get(route('pembayaran.bukti', $p->id))->assertOk();
        $this->actingAs($dokter)->get(route('pembayaran.bukti', $p->id))->assertOk();
        $this->actingAs($lain)->get(route('pembayaran.bukti', $p->id))->assertForbidden();
        $this->actingAs($dokterLain)->get(route('pembayaran.bukti', $p->id))->assertForbidden();
    }

    public function test_redirect_tidak_bergantung_referer_dan_gagal_notifikasi_membatalkan_status(): void
    {
        Storage::fake('local');
        $dokter = $this->user('dokter');
        $pasien = $this->user('pasien');
        $j = JadwalPeriksa::create(['id_dokter' => $dokter->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        $d = DaftarPoli::create(['id_pasien' => $pasien->id, 'id_jadwal' => $j->id, 'keluhan' => 'x', 'no_antrian' => 1]);
        $p = Periksa::create(['id_daftar_poli' => $d->id, 'tgl_periksa' => now(), 'biaya_periksa' => 1000]);
        $url = route('pasien.pembayaran.store', $p->id);

        // Referer = URL POST itu sendiri (kasus error 405 sebelumnya): tetap diarahkan ke halaman daftar
        $this->actingAs($pasien)->withHeader('referer', $url)
            ->post($url, ['bukti_bayar' => 'bukan-file'])
            ->assertRedirect(route('pasien.pembayaran.index'))->assertSessionHasErrors('bukti_bayar');

        // Notifikasi gagal => status kembali belum_bayar, file dibersihkan, bisa dicoba lagi
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Notifications\Events\NotificationSending::class, fn () => throw new \RuntimeException('gagal'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($pasien)->post($url, ['bukti_bayar' => UploadedFile::fake()->image('a.jpg')]);
            $this->fail('Seharusnya melempar exception');
        } catch (\RuntimeException $e) {
            $this->assertSame('belum_bayar', $p->fresh()->status_bayar);
            $this->assertNull($p->fresh()->bukti_bayar);
            $this->assertSame([], Storage::disk('local')->allFiles('bukti-bayar'));
        }
    }
}