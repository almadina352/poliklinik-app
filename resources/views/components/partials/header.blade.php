<header class="bg-base-100 border-b border-base-300 h-16 flex items-center px-6 gap-4 sticky top-0 z-30 shadow-sm">

    {{-- Mobile Hamburger --}}
    <button onclick="toggleSidebar()" class="btn btn-square btn-ghost lg:hidden">
        <i data-lucide="menu" class="w-5 h-5"></i>
    </button>

    {{-- Breadcrumb --}}
    <div class="flex-1">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-base-content/50">Poliklinik</span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-base-content/30"></i>
            <span class="font-semibold text-base-content">
                {{ $title ?? 'Dashboard' }}
            </span>
        </div>
    </div>

    {{-- Notifikasi --}}
    @php
        $notifUser = auth()->user();
        $notifUnread = $notifUser->unreadNotifications()->count();
        $notifList = $notifUser->notifications()->latest()->limit(10)->get();
    @endphp
    <div class="dropdown dropdown-end">
        <div tabindex="0" role="button" class="btn btn-ghost btn-circle relative" aria-label="Notifikasi">
            <i class="fas fa-bell text-lg"></i>
            @if($notifUnread > 0)
            <span class="absolute top-1 right-1 min-w-5 h-5 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ $notifUnread > 99 ? '99+' : $notifUnread }}
            </span>
            @endif
        </div>

        <div tabindex="0" class="dropdown-content z-50 mt-3 w-80 sm:w-96 rounded-xl bg-base-100 border border-base-300 shadow-xl">
            <div class="flex items-center justify-between px-4 py-3 border-b border-base-300">
                <span class="font-bold text-sm">Notifikasi</span>
                @if($notifUnread > 0)
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-indigo-600 hover:underline">Tandai semua dibaca</button>
                </form>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto divide-y divide-base-200">
                @forelse($notifList as $notif)
                <div class="px-4 py-3 text-sm {{ $notif->read_at ? 'bg-base-100 text-base-content/60' : 'bg-indigo-50 border-l-4 border-indigo-500' }}">
                    <p class="{{ $notif->read_at ? '' : 'font-semibold text-slate-800' }}">{{ $notif->data['pesan'] ?? '-' }}</p>
                    <div class="mt-1 flex items-center justify-between gap-2">
                        <span class="text-xs text-base-content/50" title="{{ $notif->created_at->format('d/m/Y H:i') }}">
                            {{ $notif->created_at->locale('id')->diffForHumans() }}
                        </span>
                        <div class="flex items-center gap-3">
                            @if(!empty($notif->data['url']))
                            <a href="{{ $notif->data['url'] }}" class="text-xs font-semibold text-indigo-600 hover:underline">Lihat</a>
                            @endif
                            @if(!$notif->read_at)
                            <form method="POST" action="{{ route('notifikasi.baca', $notif->id) }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-emerald-600 hover:underline">
                                    <i class="fas fa-check"></i> Tandai dibaca
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-4 py-10 text-center text-sm text-base-content/50">
                    <i class="fas fa-bell-slash text-2xl mb-2 block"></i>
                    Belum ada notifikasi.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Fullscreen --}}
    <button onclick="toggleFullscreen()" class="btn btn-square btn-ghost">
        <i id="fsIcon" class="fas fa-expand w-5 h-5"></i>
    </button>

    {{-- User Info --}}
    <div class="flex items-center gap-3">

        <div class="text-right hidden sm:block">
            <div class="text-sm font-semibold leading-tight">
                {{ auth()->user()->nama ?? 'Pengguna' }}
            </div>
            <div class="text-xs text-base-content/50 leading-tight">
                {{ auth()->user()->role ?? 'Admin Sistem' }}
            </div>
        </div>

        <div class="avatar">
            <div class="w-10 h-10 rounded-full bg-primary text-primary-content flex items-center justify-center">
                <span class="text-sm font-semibold leading-none">
                    {{ strtoupper(substr(auth()->user()->nama ?? 'U', 0, 1)) }}
                </span>
            </div>
        </div>
    </div>

</header>

<script>
    function toggleFullscreen() {
    const icon = document.getElementById('fsIcon');

    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        icon.classList.remove('fa-expand');
        icon.classList.add('fa-compress');
    } else {
        document.exitFullscreen();
        icon.classList.remove('fa-compress');
        icon.classList.add('fa-expand');
    }
}
</script>