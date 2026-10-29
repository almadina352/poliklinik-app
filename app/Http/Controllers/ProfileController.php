<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    /**
     * Hanya mengubah password akun yang sedang login (tidak ada parameter id dari request).
     * Cast 'hashed' pada model User menyimpan password dalam bentuk hash.
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $request->user()->forceFill(['password' => $request->validated('password')])->save();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Password berhasil diganti.');
    }
}