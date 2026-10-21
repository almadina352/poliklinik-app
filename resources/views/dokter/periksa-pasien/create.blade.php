{{-- resources/views/dokter/periksa-pasien/create.blade.php --}}
{{-- Ganti pembungkus <x-layouts.app> dengan layout yang dipakai halaman dokter lain --}}
{{-- Variabel dari controller: $id (id daftar_poli yang diperiksa), $obats (semua obat) --}}
@php
    $indexUrl = Route::has('periksa-pasien.index') ? route('periksa-pasien.index') : url()->previous();
    $storeUrl = Route::has('periksa-pasien.store') ? route('periksa-pasien.store') : '#';
@endphp

<x-layouts.app title="Periksa Pasien">
    <div>
        <div class="mb-4 flex items-center gap-3">
            <a href="{{ $indexUrl }}" class="text-gray-400 hover:text-gray-600" aria-label="Kembali">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <h1 class="text-xl font-bold text-gray-800">Periksa Pasien</h1>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-800">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="form-periksa" action="{{ $storeUrl }}" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="id_daftar_poli" value="{{ $id }}">
                <input type="hidden" name="obat_json" id="obat_json">
                <input type="hidden" name="biaya_periksa" id="biaya_periksa" value="0">

                {{-- Pilih Obat --}}
                <div>
                    <label for="pilih-obat" class="mb-1 block text-sm font-semibold text-gray-700">
                        Pilih Obat <span class="text-red-500">*</span>
                    </label>
                    <select id="pilih-obat"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">-- Pilih Obat --</option>
                        @foreach ($obats as $obat)
                            <option value="{{ $obat->id }}"
                                    data-nama="{{ $obat->nama_obat }}"
                                    data-harga="{{ $obat->harga }}">
                                {{ $obat->nama_obat }}
                                @if (!empty($obat->kemasan)) - {{ $obat->kemasan }} @endif
                                (Rp {{ number_format($obat->harga, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Obat Terpilih --}}
                <div>
                    <p class="mb-1 block text-sm font-semibold text-gray-700">Obat Terpilih</p>
                    <ul id="daftar-obat" class="min-h-[3rem] space-y-2"></ul>
                    <p id="obat-kosong" class="text-sm text-gray-400">Belum ada obat yang dipilih.</p>
                </div>

                {{-- Total Harga --}}
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">Total Harga</label>
                    <div id="total-harga"
                         class="w-full rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700">Rp 0</div>
                </div>

                {{-- Catatan --}}
                <div>
                    <label for="catatan" class="mb-1 block text-sm font-semibold text-gray-700">
                        Catatan <span class="font-normal text-gray-400">(Opsional)</span>
                    </label>
                    <textarea name="catatan" id="catatan" rows="4" placeholder="Masukkan catatan..."
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('catatan') }}</textarea>
                </div>

                {{-- Tombol --}}
                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-800 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="{{ $indexUrl }}"
                       class="rounded-lg bg-gray-100 px-5 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-200">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const select = document.getElementById('pilih-obat');
            const list = document.getElementById('daftar-obat');
            const kosong = document.getElementById('obat-kosong');
            const totalEl = document.getElementById('total-harga');
            const form = document.getElementById('form-periksa');

            // id => { nama, harga }
            const terpilih = new Map();

            const rupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');

            function render() {
                list.innerHTML = '';
                let total = 0;

                terpilih.forEach((obat, id) => {
                    total += obat.harga;

                    const li = document.createElement('li');
                    li.className = 'flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-sm';

                    const info = document.createElement('span');
                    info.className = 'text-gray-700';
                    info.textContent = obat.nama + ' - ' + rupiah(obat.harga);

                    const hapus = document.createElement('button');
                    hapus.type = 'button';
                    hapus.className = 'text-red-500 hover:text-red-700';
                    hapus.setAttribute('aria-label', 'Hapus ' + obat.nama);
                    hapus.innerHTML = '<i class="fas fa-times"></i>';
                    hapus.addEventListener('click', () => {
                        terpilih.delete(id);
                        render();
                    });

                    li.append(info, hapus);
                    list.appendChild(li);
                });

                kosong.style.display = terpilih.size ? 'none' : '';
                totalEl.textContent = rupiah(total);

                // data yang dibaca controller
                document.getElementById('obat_json').value =
                    JSON.stringify([...terpilih.keys()].map(Number));
                document.getElementById('biaya_periksa').value = total;
            }

            select.addEventListener('change', function () {
                const opt = select.options[select.selectedIndex];
                if (!opt.value) return;

                // satu obat hanya sekali
                if (!terpilih.has(opt.value)) {
                    terpilih.set(opt.value, {
                        nama: opt.dataset.nama,
                        harga: parseInt(opt.dataset.harga, 10) || 0,
                    });
                    render();
                }
                select.value = '';
            });

            form.addEventListener('submit', function (e) {
                if (terpilih.size === 0) {
                    e.preventDefault();
                    alert('Pilih minimal satu obat.');
                }
            });

            render();
        })();
    </script>
</x-layouts.app>