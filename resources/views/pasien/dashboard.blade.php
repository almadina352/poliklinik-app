<x-layouts.app title="Pasien Dashboard">
    <div class="space-y-6">

        {{-- ================= BANNER ANTRIAN AKTIF ================= --}}
        <div id="antrianBanner"
             class="{{ $antrianAktif ? '' : 'hidden' }} relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white shadow-lg">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-bold uppercase tracking-widest">Antrian Aktif Anda</p>

                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-white/70">Poliklinik</p>
                        <p id="bnPoli" class="text-xl font-bold">{{ $antrianAktif['poli'] ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-white/70">Dokter</p>
                        <p id="bnDokter" class="font-semibold">{{ $antrianAktif['dokter'] ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-white/70">Jadwal Periksa</p>
                        <p id="bnJadwal" class="font-semibold">{{ $antrianAktif['jadwal'] ?? '-' }}</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex min-w-[130px] flex-col items-center justify-center rounded-2xl bg-white/15 px-6 py-5">
                        <p class="text-xs text-white/80">Nomor Anda</p>
                        <p id="bnNomor" class="text-5xl font-extrabold">{{ $antrianAktif['no_antrian'] ?? '-' }}</p>
                    </div>
                    <div class="flex min-w-[150px] flex-col items-center justify-center rounded-2xl bg-white px-6 py-5 text-indigo-700 shadow">
                        <p class="text-[11px] font-bold uppercase tracking-wide">Sedang Dilayani</p>
                        <p id="bnDilayani" class="text-5xl font-extrabold">{{ $antrianAktif['dilayani'] ?? '-' }}</p>
                        <p class="mt-1 flex items-center gap-1 text-[10px] text-slate-500">
                            <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                            Live Update
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pesan kosong jika tidak ada antrian aktif --}}
        <div id="antrianKosong"
             class="{{ $antrianAktif ? 'hidden' : '' }} rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center">
            <p class="font-semibold text-slate-700">Anda belum memiliki antrian aktif.</p>
            <p class="mt-1 text-sm text-slate-500">
                Silakan daftar melalui menu
                <a href="{{ route('pasien.daftar') }}" class="font-semibold text-indigo-600 hover:underline">Pendaftaran Periksa</a>.
            </p>
        </div>

        {{-- ================= TABEL JADWAL ================= --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="font-bold text-slate-800">Jadwal Poliklinik &amp; Dokter</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3">No</th>
                            <th class="px-6 py-3">Poli</th>
                            <th class="px-6 py-3">Dokter</th>
                            <th class="px-6 py-3">Hari</th>
                            <th class="px-6 py-3">Jam Periksa</th>
                            <th class="px-6 py-3 text-center">Sedang Dilayani</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jadwals as $jadwal)
                            <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                                <td class="px-6 py-3 text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $jadwal->dokter->poli->nama_poli ?? '-' }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $jadwal->dokter->nama ?? '-' }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $jadwal->hari }}</td>
                                <td class="px-6 py-3 text-gray-700">
                                    {{ \Illuminate\Support\Str::substr($jadwal->jam_mulai, 0, 5) }}
                                    - {{ \Illuminate\Support\Str::substr($jadwal->jam_selesai, 0, 5) }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span data-dilayani="{{ $jadwal->id }}"
                                          class="inline-flex min-w-8 justify-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">
                                        {{ $dilayani[$jadwal->id] ?? '-' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">Belum ada jadwal periksa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Real-time: Laravel Echo (Reverb) mendengarkan channel publik "antrian".
        // Tanpa reload halaman. Jika Echo belum dikonfigurasi, jatuh ke polling.
        (function () {
            const url = @json(route('pasien.antrian.live'));
            const $ = (id) => document.getElementById(id);
            const val = (v) => (v === null || v === undefined ? '-' : v);

            function renderTabel(dilayani) {
                document.querySelectorAll('[data-dilayani]').forEach((el) => {
                    el.textContent = val(dilayani[el.dataset.dilayani]);
                });
            }

            function render(data) {
                const a = data.aktif;
                $('antrianBanner').classList.toggle('hidden', !a);
                $('antrianKosong').classList.toggle('hidden', !!a);

                if (a) {
                    $('bnPoli').textContent = a.poli;
                    $('bnDokter').textContent = a.dokter;
                    $('bnJadwal').textContent = a.jadwal;
                    $('bnNomor').textContent = a.no_antrian;
                    $('bnDilayani').textContent = val(a.dilayani);
                }

                renderTabel(data.jadwal);
            }

            async function refresh() {
                try {
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (res.ok) render(await res.json());
                } catch (e) { /* abaikan */ }
            }

            if (window.Echo) {
                window.Echo.channel('antrian').listen('.antrian.diperbarui', (e) => {
                    renderTabel(e.dilayani);   // tabel langsung dari payload
                    refresh();                 // banner pasien (data pribadi) diambil via endpoint terautentikasi
                });

                // Sinkronkan ulang setelah koneksi WebSocket (re)connect agar tidak ada update terlewat
                window.Echo.connector.pusher.connection.bind('connected', refresh);
            } else {
                setInterval(() => { if (!document.hidden) refresh(); }, 5000);
                document.addEventListener('visibilitychange', refresh);
            }
        })();
    </script>
    @endpush
</x-layouts.app>