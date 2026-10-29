<?php

namespace Tests\Feature;

use App\Models\DaftarPoli;
use App\Models\JadwalPeriksa;
use App\Models\Periksa;
use App\Models\User;
use App\Notifications\PembayaranDiunggahNotification;
use App\Notifications\PembayaranLunasNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'nama' => ucfirst($role) . ' ' . uniqid(),
            'email' => uniqid() . '@test.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function periksa(User $dokter, User $pasien): Periksa
    {
        $jadwal = JadwalPeriksa::create([
            'id_dokter' => $dokter->id, 'hari' => 'Senin',
            'jam_mulai' => '08:00', 'jam_selesai' => '12:00',
        ]);
        $daftar = DaftarPoli::create([
            'id_pasien' => $pasien->id, 'id_jadwal' => $jadwal->id,
            'keluhan' => 'Demam', 'no_antrian' => 1,
        ]);

        return Periksa::create([
            'id_daftar_poli' => $daftar->id, 'tgl_periksa' => now(), 'biaya_periksa' => 200000,
        ]);
    }

    public function test_dokter_menerima_satu_notifikasi_saat_pasien_upload_bukti(): void
    {
        Storage::fake('public');
        $dokter = $this->user('dokter');
        $pasien = $this->user('pasien');
        $periksa = $this->periksa($dokter, $pasien);

        $file = UploadedFile::fake()->image('bukti.jpg');
        $this->actingAs($pasien)->post(route('pasien.pembayaran.store', $periksa->id), ['bukti_bayar' => $file])
            ->assertSessionHas('success');
        // Kirim ulang: tidak boleh menghasilkan notifikasi kedua
        $this->actingAs($pasien)->post(route('pasien.pembayaran.store', $periksa->id), ['bukti_bayar' => UploadedFile::fake()->image('b2.jpg')])
            ->assertSessionHas('error');

        $this->assertCount(1, $dokter->notifications);
        $this->assertSame(PembayaranDiunggahNotification::class, $dokter->notifications->first()->type);
        $this->assertCount(0, $pasien->notifications);
        $this->assertSame('menunggu_verifikasi', $periksa->fresh()->status_bayar);
    }

    public function test_pasien_menerima_satu_notifikasi_saat_admin_verifikasi(): void
    {
        $dokter = $this->user('dokter');
        $pasien = $this->user('pasien');
        $admin = $this->user('admin');
        $periksa = $this->periksa($dokter, $pasien);
        $periksa->update(['status_bayar' => 'menunggu_verifikasi']);

        $this->actingAs($admin)->post(route('admin.pembayaran.verifikasi', $periksa->id));
        $this->actingAs($admin)->post(route('admin.pembayaran.verifikasi', $periksa->id)); // ganda

        $this->assertCount(1, $pasien->notifications);
        $this->assertSame(PembayaranLunasNotification::class, $pasien->notifications->first()->type);
        $this->assertCount(0, $dokter->notifications);
        $this->assertSame('lunas', $periksa->fresh()->status_bayar);
    }

    public function test_pengguna_tidak_bisa_menandai_notifikasi_milik_orang_lain(): void
    {
        $a = $this->user('pasien');
        $b = $this->user('pasien');
        $periksa = $this->periksa($this->user('dokter'), $a);
        $a->notify(new PembayaranLunasNotification($periksa));
        $notif = $a->notifications()->first();

        $this->actingAs($b)->post(route('notifikasi.baca', $notif->id))->assertNotFound();
        $this->assertNull($notif->fresh()->read_at);

        $this->actingAs($a)->post(route('notifikasi.baca', $notif->id))->assertRedirect();
        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_navbar_menampilkan_jumlah_belum_dibaca_dan_pesan_kosong(): void
    {
        $this->withoutVite();
        $pasien = $this->user('pasien');
        $this->actingAs($pasien)->get(route('pasien.pembayaran.index'))
            ->assertOk()->assertSee('Belum ada notifikasi.');

        $periksa = $this->periksa($this->user('dokter'), $pasien);
        $pasien->notify(new PembayaranLunasNotification($periksa));

        $this->actingAs($pasien)->get(route('pasien.pembayaran.index'))
            ->assertOk()->assertSee('Tandai dibaca')->assertDontSee('Belum ada notifikasi.');
    }
}