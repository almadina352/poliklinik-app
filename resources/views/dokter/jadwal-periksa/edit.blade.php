<x-layouts.app title="Edit Jadwal Periksa">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('jadwal-periksa.index') }}"
            class="flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <h2 class="text-xl font-bold text-slate-800">
            Edit Jadwal Periksa
        </h2>
    </div>

    {{-- Card --}}
    <div class="card bg-base-100 shadow-md rounded-2xl border border-slate-200">
        <div class="card-body p-7">

            <form action="{{ route('jadwal-periksa.update', $jadwal->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Hari --}}
                <div class="form-control mb-5 w-full">
                    <label class="label pb-1">
                        <span class="text-sm font-semibold text-gray-700">
                            Hari <span class="text-red-500">*</span>
                        </span>
                    </label>

                    <select name="hari" class="select select-bordered w-full rounded-lg text-sm @error('hari') select-error @enderror" required>
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari)
                            <option value="{{ $hari }}" {{ old('hari', $jadwal->hari) == $hari ? 'selected' : '' }}>{{ $hari }}</option>
                        @endforeach
                    </select>

                    @error('hari')
                    <label class="label pt-1">
                        <span class="label-text-alt text-red-500">{{ $message }}</span>
                    </label>
                    @enderror
                </div>

                {{-- Jam Mulai --}}
                <div class="form-control mb-5 w-full">
                    <label class="label pb-1">
                        <span class="text-sm font-semibold text-gray-700">
                            Jam Mulai <span class="text-red-500">*</span>
                        </span>
                    </label>

                    <input type="time" name="jam_mulai" value="{{ old('jam_mulai', $jadwal->jam_mulai) }}"
                        class="input input-bordered w-full rounded-lg text-sm @error('jam_mulai') input-error @enderror" required>

                    @error('jam_mulai')
                    <label class="label pt-1">
                        <span class="label-text-alt text-red-500">{{ $message }}</span>
                    </label>
                    @enderror
                </div>

                {{-- Jam Selesai --}}
                <div class="form-control mb-5 w-full">
                    <label class="label pb-1">
                        <span class="text-sm font-semibold text-gray-700">
                            Jam Selesai <span class="text-red-500">*</span>
                        </span>
                    </label>

                    <input type="time" name="jam_selesai" value="{{ old('jam_selesai', $jadwal->jam_selesai) }}"
                        class="input input-bordered w-full rounded-lg text-sm @error('jam_selesai') input-error @enderror" required>

                    @error('jam_selesai')
                    <label class="label pt-1">
                        <span class="label-text-alt text-red-500">{{ $message }}</span>
                    </label>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="form-control mb-6 w-full">
                    <label class="label pb-1">
                        <span class="text-sm font-semibold text-gray-700">Status</span>
                    </label>
                    <select name="status" class="select select-bordered w-full rounded-lg text-sm">
                        <option value="aktif" {{ old('status', $jadwal->status) == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="tidak aktif" {{ old('status', $jadwal->status) == 'tidak aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="flex gap-3">
                    <button type="submit"
                        class="flex items-center gap-2 px-6 py-2.5 bg-[#2d4499] hover:bg-[#1e2d6b] text-white rounded-lg text-sm font-semibold transition">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>

                    <a href="{{ route('jadwal-periksa.index') }}"
                        class="flex items-center gap-2 px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-lg text-sm font-semibold transition">
                        Batal
                    </a>
                </div>

            </form>
        </div>
    </div>

</x-layouts.app>