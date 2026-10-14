<x-layouts.app title="Data Obat">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800">
            Data Obat
        </h2>

        <a href="{{ route('obat.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-xl text-sm font-semibold transition">
            <i class="fas fa-plus text-sm"></i>
            Tambah Obat
        </a>
    </div>

    {{-- Alert Notification --}}
    @if(session('message'))
    <div class="mb-4 p-4 rounded-xl bg-emerald-100 text-emerald-700 border border-emerald-200 text-sm font-semibold flex items-center gap-2">
        <i class="fas fa-circle-check"></i>
        {{ session('message') }}
    </div>
    @endif

    {{-- Card Table --}}
    <div class="card bg-base-100 shadow-md rounded-2 border">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead class="bg-slate-100 text-slate-600 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">No</th>
                            <th class="px-6 py-4">Nama Obat</th>
                            <th class="px-6 py-4">Kemasan</th>
                            <th class="px-6 py-4">Harga</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700">
                        @forelse($obats as $index => $obat)
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-slate-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $obat->nama_obat }}</td>
                            <td class="px-6 py-4">{{ $obat->kemasan }}</td>
                            <td class="px-6 py-4">Rp {{ number_format($obat->harga, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('obat.edit', $obat->id) }}" class="inline-flex items-center gap-1 px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-pen-to-square text-xs"></i>
                                        Edit
                                    </a>
                                    <form action="{{ route('obat.destroy', $obat->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Yakin ingin menghapus obat ini?')" class="inline-flex items-center gap-1 px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition">
                                            <i class="fas fa-trash text-xs"></i>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-16 text-slate-400">
                                <i class="fas fa-inbox text-3xl mb-3 block"></i>
                                Belum ada data obat
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layouts.app>