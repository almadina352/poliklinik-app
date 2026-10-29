<x-layouts.app title="Riwayat Pendaftaran">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Riwayat Pendaftaran Poli</h2>
    </div>

    <div class="card bg-base-100 shadow-md border">
        <div class="card-body p-0 overflow-x-auto">
            <table class="table w-full">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Poli</th>
                        <th class="px-6 py-4">Dokter</th>
                        <th class="px-6 py-4">Jadwal</th>
                        <th class="px-6 py-4">Tgl Daftar</th>
                        <th class="px-6 py-4 text-center">No. Antrian</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                    @forelse($riwayats as $r)
                    @php($j = $r->jadwalPeriksa)
                    <tr class="border-t border-slate-100 hover:bg-slate-50 transition">
                        <td class="px-6 py-4 font-semibold">{{ $j->dokter->poli->nama_poli ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $j->dokter->nama ?? '-' }}</td>
                        <td class="px-6 py-4">
                            {{ $j->hari }}
                            <span class="text-xs text-slate-500">
                                ({{ \Illuminate\Support\Str::substr($j->jam_mulai, 0, 5) }} - {{ \Illuminate\Support\Str::substr($j->jam_selesai, 0, 5) }})
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $r->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-center font-bold">{{ $r->no_antrian }}</td>
                        <td class="px-6 py-4">
                            @if($r->periksas->isNotEmpty())
                                <span class="badge badge-success text-white">Sudah diperiksa</span>
                            @else
                                <span class="badge badge-warning">Menunggu</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($r->periksas->isNotEmpty())
                            <a href="{{ route('pasien.riwayat.show', $r->id) }}"
                               class="btn btn-sm bg-blue-500 hover:bg-blue-600 text-white border-none rounded-lg px-4">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-12 text-slate-400">Belum ada riwayat pendaftaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>