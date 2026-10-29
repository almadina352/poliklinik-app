<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GantiPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'pasien', string $pass = 'passwordlama'): User
    {
        return User::create(['nama' => $role . uniqid(), 'email' => uniqid() . '@t.com', 'password' => $pass, 'role' => $role]);
    }

    private function ganti(User $u, array $data)
    {
        return $this->actingAs($u)->from(route('profile.edit'))->put(route('profile.password.update'), $data);
    }

    public function test_semua_role_bisa_membuka_profile_dan_tamu_ditolak(): void
    {
        $this->withoutVite();
        foreach (['admin', 'dokter', 'pasien'] as $role) {
            $this->actingAs($this->user($role))->get(route('profile.edit'))
                ->assertOk()->assertSee('Password Lama')->assertSee('Konfirmasi Password Baru')->assertSee('Profile');
        }
        auth()->logout();
        $this->get(route('profile.edit'))->assertRedirect();
        $this->put(route('profile.password.update'), [])->assertRedirect();
    }

    public function test_berhasil_ganti_password_tersimpan_sebagai_hash(): void
    {
        $u = $this->user();

        $this->ganti($u, ['current_password' => 'passwordlama', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertRedirect(route('profile.edit'))->assertSessionHas('success')->assertSessionHasNoErrors();

        $raw = \DB::table('users')->where('id', $u->id)->value('password');
        $this->assertNotSame('passwordbaru1', $raw);          // bukan teks biasa
        $this->assertTrue(Hash::check('passwordbaru1', $raw)); // hash valid
        $this->assertFalse(Hash::check('passwordlama', $raw));
    }

    public function test_validasi_dan_pesan_error_per_kolom(): void
    {
        $u = $this->user();
        $hashAwal = $u->fresh()->password;

        $this->ganti($u, ['current_password' => '', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertSessionHasErrors(['current_password' => 'Password lama wajib diisi.']);
        $this->ganti($u, ['current_password' => 'salah', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1'])
            ->assertSessionHasErrors(['current_password' => 'Password lama tidak sesuai.']);
        $this->ganti($u, ['current_password' => 'passwordlama', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors(['password' => 'Password baru minimal 8 karakter.']);
        $this->ganti($u, ['current_password' => 'passwordlama', 'password' => 'passwordbaru1', 'password_confirmation' => 'beda12345'])
            ->assertSessionHasErrors(['password' => 'Konfirmasi password baru tidak cocok.']);
        $this->ganti($u, ['current_password' => 'passwordlama', 'password' => 'passwordlama', 'password_confirmation' => 'passwordlama'])
            ->assertSessionHasErrors(['password' => 'Password baru tidak boleh sama dengan password lama.']);

        $this->assertSame($hashAwal, $u->fresh()->password); // tidak ada yang berubah
    }

    public function test_hanya_mengubah_password_akun_sendiri(): void
    {
        $a = $this->user('pasien');
        $b = $this->user('dokter', 'passwordb123');
        $hashB = $b->fresh()->password;

        // Mencoba menyisipkan id/email milik orang lain tidak berpengaruh
        $this->ganti($a, ['id' => $b->id, 'email' => $b->email, 'current_password' => 'passwordlama',
            'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1']);

        $this->assertTrue(Hash::check('passwordbaru1', $a->fresh()->password));
        $this->assertSame($hashB, $b->fresh()->password);
    }

    public function test_menu_role_tetap_tampil_di_halaman_profile(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user('dokter'))->get(route('profile.edit'))->assertSee('Menu Dokter')->assertDontSee('Menu Admin');
        $this->actingAs($this->user('admin'))->get(route('profile.edit'))->assertSee('Menu Admin');
    }
}