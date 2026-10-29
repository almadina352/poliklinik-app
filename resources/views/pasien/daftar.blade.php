{{-- resources/views/pasien/daftar.blade.php --}}
{{-- Ganti pembungkus <x-layouts.app> dengan layout yang dipakai pasien/dashboard.blade.php --}}
<x-layouts.app title="Daftar Poli">
    <div class="mx-auto max-w-2xl">
        <div class="rounded-xl bg-white p-6 shadow-sm">

            <h1 class="mb-6 text-center text-xl font-bold text-gray-800">
                <span aria-hidden="true">📋</span> Pendaftaran Poli
            </h1>

            {{-- Notifikasi sukses / gagal --}}
            @if (session('message'))
                <div class="mb-4 rounded-lg px-4 py-3 text-sm {{ session('type') === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ session('message') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-800">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($antrianAktif)
                <div class="mb-4 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-800">
                    Anda masih memiliki antrian aktif di
                    <strong>{{ $antrianAktif->jadwalPeriksa->dokter->poli->nama_poli ?? '-' }}</strong>
                    (nomor {{ $antrianAktif->no_antrian }}). Anda dapat mendaftar kembali setelah pemeriksaan selesai.
                </div>
            @endif

            <form action="{{ route('pasien.daftar.submit') }}" method="POST" class="space-y-4">
                <fieldset @disabled($antrianAktif) class="space-y-4">
                @csrf

                {{-- Nomor Rekam Medis --}}
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Nomor Rekam Medis</label>
                    <input type="text" value="{{ $user->no_rm ?? '-' }}" readonly
                           class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-600">
                </div>

                {{-- Pilih Poli --}}
                <div>
                    <label for="id_poli" class="mb-1 block text-sm font-semibold text-gray-700">Pilih Poli</label>
                    <select name="id_poli" id="id_poli" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih Poli --</option>
                        @foreach ($polis as $poli)
                            <option value="{{ $poli->id }}" @selected(old('id_poli') == $poli->id)>
                                {{ $poli->nama_poli }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Pilih Jadwal Periksa --}}
                <div>
                    <label for="id_jadwal" class="mb-1 block text-sm font-semibold text-gray-700">Pilih Jadwal Periksa</label>
                    <select name="id_jadwal" id="id_jadwal" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih Jadwal --</option>
                        @foreach ($jadwals as $jadwal)
                            <option value="{{ $jadwal->id }}"
                                    data-poli="{{ $jadwal->dokter->poli->id ?? '' }}"
                                    @selected(old('id_jadwal') == $jadwal->id)>
                                {{ $jadwal->dokter->nama ?? '-' }} — {{ $jadwal->hari }}
                                ({{ \Illuminate\Support\Str::substr($jadwal->jam_mulai, 0, 5) }}
                                 - {{ \Illuminate\Support\Str::substr($jadwal->jam_selesai, 0, 5) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Keluhan --}}
                <div>
                    <label for="keluhan" class="mb-1 block text-sm font-semibold text-gray-700">Keluhan</label>
                    <textarea name="keluhan" id="keluhan" rows="3" placeholder="Tulis keluhan anda..."
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('keluhan') }}</textarea>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Daftar Poli
                    </button>
                </div>
                </fieldset>
            </form>
        </div>
    </div>

    <script>
        // Tampilkan hanya jadwal milik poli yang dipilih
        const poliSelect = document.getElementById('id_poli');
        const jadwalSelect = document.getElementById('id_jadwal');

        function filterJadwal(resetValue = true) {
            const poli = poliSelect.value;
            if (resetValue) jadwalSelect.value = '';
            [...jadwalSelect.options].forEach(opt => {
                if (!opt.value) return;
                const hide = poli && opt.dataset.poli !== poli;
                opt.hidden = hide;
                opt.disabled = hide;
            });
        }

        poliSelect.addEventListener('change', () => filterJadwal(true));
        filterJadwal(false); // untuk kasus old() setelah validasi gagal
    </script>
</x-layouts.app>