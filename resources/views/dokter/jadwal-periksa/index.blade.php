<x-layouts.app title="Jadwal Periksa">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Jadwal Periksa</h2>
        <a href="{{ route('jadwal-periksa.create') }}"
            class="flex items-center gap-2 px-5 py-2.5 bg-[#2d4499] hover:bg-[#1e2d6b] text-white rounded-lg text-sm font-semibold transition shadow-sm">
            <i class="fas fa-plus text-xs"></i>
            Tambah Jadwal
        </a>
    </div>

    @if(session('message'))
    <div class="mb-4 p-4 rounded-xl bg-emerald-100 text-emerald-700 border border-emerald-200 text-sm font-semibold flex items-center gap-2">
        <i class="fas fa-circle-check"></i>
        {{ session('message') }}
    </div>
    @endif

    <div class="card bg-base-100 shadow-md rounded-2xl border border-slate-200">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">NO</th>
                            <th class="px-6 py-4">HARI</th>
                            <th class="px-6 py-4">JAM MULAI</th>
                            <th class="px-6 py-4">JAM SELESAI</th>
                            <th class="px-6 py-4">STATUS</th>
                            <th class="px-6 py-4 text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700">
                        @forelse($jadwals as $index => $jadwal)
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-medium text-slate-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $jadwal->hari }}</td>
                            <td class="px-6 py-4">{{ $jadwal->jam_mulai }}</td>
                            <td class="px-6 py-4">{{ $jadwal->jam_selesai }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $jadwal->status == 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($jadwal->status ?? 'aktif') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('jadwal-periksa.edit', $jadwal->id) }}"
                                        class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a>
                                    <form action="{{ route('jadwal-periksa.destroy', $jadwal->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Yakin ingin menghapus jadwal ini?')"
                                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition">
                                            <i class="fas fa-trash mr-1"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="fas fa-calendar-xmark text-3xl"></i>
                                    <span>Belum ada jadwal periksa</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layouts.app>