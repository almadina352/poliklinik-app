<x-layouts.app title="Verifikasi Pembayaran">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Verifikasi Pembayaran</h2>
        <p class="text-sm text-slate-500 mt-1">
            <span class="font-semibold text-amber-600">{{ $periksas->where('status_bayar', 'menunggu_verifikasi')->count() }}</span>
            tagihan menunggu verifikasi
        </p>
    </div>

    <div class="card bg-base-100 shadow-md border">
        <div class="card-body p-0 overflow-x-auto">
            <table class="table w-full">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Pasien</th>
                        <th class="px-6 py-4">Dokter</th>
                        <th class="px-6 py-4">Biaya</th>
                        <th class="px-6 py-4">Bukti</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                    @forelse($periksas as $p)
                    <tr class="border-t border-slate-100">
                        <td class="px-6 py-4 font-semibold">{{ $p->daftarPoli->pasien->nama ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $p->daftarPoli->jadwalPeriksa->dokter->nama ?? '-' }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($p->biaya_periksa, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($p->bukti_bayar)
                            <a href="{{ route('pembayaran.bukti', $p->id) }}" target="_blank" class="text-indigo-600 text-xs font-semibold hover:underline">Lihat bukti</a>
                            @else - @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($p->status_bayar === 'lunas')
                                <span class="badge badge-success text-white">Lunas</span>
                            @else
                                <span class="badge badge-warning">Menunggu verifikasi</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($p->status_bayar === 'menunggu_verifikasi')
                            <form method="POST" action="{{ route('admin.pembayaran.verifikasi', $p->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-success text-white">Verifikasi (Lunas)</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-12 text-slate-400">Belum ada pembayaran masuk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>