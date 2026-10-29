<x-layouts.app title="Profile">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Profile</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Info akun --}}
        <div class="card bg-base-100 shadow-sm rounded-2xl border border-slate-200 h-fit">
            <div class="card-body p-0">
                <div class="px-6 py-4 border-b border-slate-200"><h3 class="font-bold text-slate-800">Akun Saya</h3></div>
                <div class="px-6 py-5 flex flex-col gap-3 text-sm">
                    <div><span class="block text-slate-500 text-xs">Nama</span><span class="font-semibold text-slate-800">{{ $user->nama }}</span></div>
                    <div><span class="block text-slate-500 text-xs">Email</span><span class="text-slate-800">{{ $user->email }}</span></div>
                    <div><span class="block text-slate-500 text-xs">Role</span><span class="text-slate-800 capitalize">{{ $user->role }}</span></div>
                </div>
            </div>
        </div>

        {{-- Form ganti password --}}
        <div class="card bg-base-100 shadow-sm rounded-2xl border border-slate-200 lg:col-span-2">
            <div class="card-body p-0">
                <div class="px-6 py-4 border-b border-slate-200"><h3 class="font-bold text-slate-800">Ganti Password</h3></div>

                <form method="POST" action="{{ route('profile.password.update') }}" class="px-6 py-5 flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-slate-700 mb-1">
                            Password Lama <span class="text-red-500">*</span>
                        </label>
                        <input id="current_password" type="password" name="current_password" autocomplete="current-password" required
                            class="w-full px-4 py-2 border-2 rounded-lg focus:border-primary focus:outline-none @error('current_password') border-red-500 @enderror">
                        @error('current_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">
                            Password Baru <span class="text-red-500">*</span>
                        </label>
                        <input id="password" type="password" name="password" autocomplete="new-password" required minlength="8"
                            class="w-full px-4 py-2 border-2 rounded-lg focus:border-primary focus:outline-none @error('password') border-red-500 @enderror">
                        <p class="text-xs text-slate-400 mt-1">Minimal 8 karakter.</p>
                        @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1">
                            Konfirmasi Password Baru <span class="text-red-500">*</span>
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required
                            class="w-full px-4 py-2 border-2 rounded-lg focus:border-primary focus:outline-none">
                    </div>

                    <div>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold text-sm transition">
                            <i class="fas fa-key mr-1"></i> Simpan Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>