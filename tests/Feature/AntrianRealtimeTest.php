<?php

namespace Tests\Feature;

use App\Events\AntrianDiperbarui;
use App\Models\DaftarPoli;
use App\Models\JadwalPeriksa;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AntrianRealtimeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?int $poli = null): User
    {
        return User::create(['nama' => $role . uniqid(), 'email' => uniqid() . '@t.com', 'password' => 'password', 'role' => $role, 'id_poli' => $poli]);
    }

    public function test_event_disiarkan_di_channel_publik_dengan_payload_nomor_dilayani(): void
    {
        $dokter = $this->user('dokter');
        $jadwal = JadwalPeriksa::create(['id_dokter' => $dokter->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        DaftarPoli::create(['id_pasien' => $this->user('pasien')->id, 'id_jadwal' => $jadwal->id, 'keluhan' => 'x', 'no_antrian' => 1]);
        DaftarPoli::create(['id_pasien' => $this->user('pasien')->id, 'id_jadwal' => $jadwal->id, 'keluhan' => 'x', 'no_antrian' => 2]);

        $e = new AntrianDiperbarui();

        $this->assertEquals([new Channel('antrian')], $e->broadcastOn());
        $this->assertSame('antrian.diperbarui', $e->broadcastAs());
        $this->assertEquals([$jadwal->id => 1], $e->broadcastWith()['dilayani']->all());
    }

    public function test_pendaftaran_poli_menyiarkan_event_sekali(): void
    {
        Event::fake([AntrianDiperbarui::class]);
        $poli = Poli::create(['nama_poli' => 'Umum']);
        $dokter = $this->user('dokter', $poli->id);
        $jadwal = JadwalPeriksa::create(['id_dokter' => $dokter->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        $pasien = $this->user('pasien');

        $this->actingAs($pasien)->post(route('pasien.daftar.submit'), [
            'id_poli' => $poli->id, 'id_jadwal' => $jadwal->id, 'keluhan' => 'Batuk',
        ])->assertRedirect(route('pasien.dashboard'));

        Event::assertDispatchedTimes(AntrianDiperbarui::class, 1);
    }

    public function test_pendaftaran_ditolak_tidak_menyiarkan_event(): void
    {
        Event::fake([AntrianDiperbarui::class]);
        $poli = Poli::create(['nama_poli' => 'Umum']);
        $jadwal = JadwalPeriksa::create(['id_dokter' => $this->user('dokter', $poli->id)->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        $pasien = $this->user('pasien');
        $payload = ['id_poli' => $poli->id, 'id_jadwal' => $jadwal->id, 'keluhan' => 'Batuk'];

        $this->actingAs($pasien)->post(route('pasien.daftar.submit'), $payload);
        $this->actingAs($pasien)->post(route('pasien.daftar.submit'), $payload); // ditolak: masih aktif

        Event::assertDispatchedTimes(AntrianDiperbarui::class, 1);
    }

    public function test_pemeriksaan_selesai_menyiarkan_event(): void
    {
        Event::fake([AntrianDiperbarui::class]);
        $dokter = $this->user('dokter');
        $jadwal = JadwalPeriksa::create(['id_dokter' => $dokter->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        $daftar = DaftarPoli::create(['id_pasien' => $this->user('pasien')->id, 'id_jadwal' => $jadwal->id, 'keluhan' => 'x', 'no_antrian' => 1]);

        $this->actingAs($dokter)->post(route('periksa-pasien.store'), [
            'id_daftar_poli' => $daftar->id, 'obat_json' => '[]', 'biaya_periksa' => 50000,
        ]);

        Event::assertDispatchedTimes(AntrianDiperbarui::class, 1);
    }

    public function test_kegagalan_siaran_tidak_menggagalkan_proses(): void
    {
        Event::listen(AntrianDiperbarui::class, fn () => throw new \RuntimeException('Reverb mati'));

        AntrianDiperbarui::kirim(); // tidak melempar exception
        $this->assertTrue(true);
    }
}