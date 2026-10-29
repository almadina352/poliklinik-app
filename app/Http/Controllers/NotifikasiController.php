<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    /**
     * Tandai satu notifikasi sebagai dibaca.
     * Dicari lewat relasi milik user login, sehingga notifikasi pengguna lain => 404.
     */
    public function baca(string $id)
    {
        $notif = Auth::user()->notifications()->findOrFail($id);
        $notif->markAsRead();

        return back();
    }

    public function bacaSemua()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back();
    }
}