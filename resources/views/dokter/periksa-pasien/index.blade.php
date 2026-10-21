{{-- resources/views/dokter/periksa-pasien/index.blade.php --}}
{{-- Ganti pembungkus <x-layouts.app> dengan layout yang dipakai halaman dokter lain (mis. jadwal-periksa) --}}
{{-- Variabel dari controller: $daftarPasien (koleksi DaftarPoli dengan relasi pasien dan periksas) --}}
<x-layouts.app title="Periksa Pasien">
    <div>
        <h1 class="mb-4 text-xl font-bold text-gray-800">Periksa Pasien</h1>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-100 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Pasien</th>
                            <th class="px-6 py-4">Keluhan</th>
                            <th class="px-6 py-4">No Antrian</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftarPasien as $item)
                            <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                                <td class="px-6 py-3 text-gray-500">{{ $item->id }}</td>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $item->pasien->nama ?? '-' }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $item->keluhan ?? '-' }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $item->no_antrian }}</td>
                                <td class="px-6 py-3 text-right">
                                    @if ($item->periksas->isNotEmpty())
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                            <span class="h-2 w-2 rounded-full bg-green-600"></span>
                                            Sudah Diperiksa
                                        </span>
                                    @else
                                        <a href="{{ Route::has('periksa-pasien.create') ? route('periksa-pasien.create', $item->id) : '#' }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-300">
                                            <i class="fas fa-stethoscope"></i>
                                            Periksa
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                    Belum ada pasien yang mendaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>