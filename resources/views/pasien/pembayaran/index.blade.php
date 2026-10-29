<x-layouts.app title="Pembayaran">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Pembayaran Pemeriksaan</h2>
    </div>

    <div class="card bg-base-100 shadow-md border">
        <div class="card-body p-0 overflow-x-auto">
            <table class="table w-full">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Poli / Dokter</th>
                        <th class="px-6 py-4">Biaya</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Bukti Bayar</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                    @forelse($periksas as $p)
                    <tr class="border-t border-slate-100">
                        <td class="px-6 py-4">{{ \Carbon\Carbon::parse($p->tgl_periksa)->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4">
                            <div class="font-semibold">{{ $p->daftarPoli->jadwalPeriksa->dokter->poli->nama_poli ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $p->daftarPoli->jadwalPeriksa->dokter->nama ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">Rp {{ number_format($p->biaya_periksa, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($p->status_bayar === 'lunas')
                                <span class="badge badge-success text-white">Lunas</span>
                            @elseif($p->status_bayar === 'menunggu_verifikasi')
                                <span class="badge badge-warning">Menunggu verifikasi</span>
                            @else
                                <span class="badge badge-error text-white">Belum dibayar</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($p->status_bayar === 'belum_bayar')
                            <form method="POST" action="{{ route('pasien.pembayaran.store', $p->id) }}" enctype="multipart/form-data" class="flex justify-end items-center gap-2">
                                @csrf
                                <input type="file" name="bukti_bayar" accept="image/png,image/jpeg" required class="file-input file-input-bordered file-input-sm w-52">
                                <button class="btn btn-sm btn-primary">Unggah</button>
                            </form>
                            @error('bukti_bayar')@if(session('id_gagal') == $p->id)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@endif @enderror
                            @elseif($p->bukti_bayar)
                            <a href="{{ route('pembayaran.bukti', $p->id) }}" target="_blank" class="text-indigo-600 text-xs font-semibold hover:underline">Lihat bukti</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-12 text-slate-400">Belum ada pemeriksaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>