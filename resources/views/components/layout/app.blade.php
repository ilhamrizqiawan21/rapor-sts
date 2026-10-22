@props([
    'title' => 'Rapor STS',
    'breadcrumbs' => [],
    'userRole' => null,
    'userName' => null,
    'userInitials' => null,
    'brandName' => null,
    'brandSub' => null,
    'semesterBadge' => null,
    'topbarColor' => null,
])

@php
    $currentUser = auth()->user();
    $role = $userRole ?? ($currentUser?->role instanceof \App\Enums\Role ? $currentUser->role->value : ($currentUser?->role ?? 'operator'));
    $displayName = $userName ?? ($currentUser?->name ?? 'Pengguna');

    if (! $userInitials) {
        $words = explode(' ', trim($displayName));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }
        $userInitials = $initials ?: 'AD';
    }

    $activePeriod = $activePeriod ?? (app()->bound('activePeriod') ? app('activePeriod') : null);
    $badge = $semesterBadge ?? ($activePeriod ? $activePeriod->badge_text : 'SEMESTER GANJIL 2026/2027');

    $appSetting = \Illuminate\Support\Facades\Schema::hasTable('app_settings') ? \App\Models\AppSetting::first() : null;
    $brand = $brandName ?? ($appSetting->app_name ?? 'Rapor STS');
    $sub = $brandSub ?? ($appSetting->app_subtitle ?? '');
    $topbar = $topbarColor ?? ($appSetting->topbar_color ?? null);
@endphp

<!doctype html>
<html lang="id" @if($topbar) style="--topbar: {{ $topbar }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ $brand }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app">
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="window.toggleSidebar ? window.toggleSidebar() : null"></div>
    <aside class="side" id="appSidebar">
        <div class="brand">
            <div class="logo">RS</div>
            <div>
                <div class="bname">{{ $brand }}</div>
                <div class="bsub">{{ $sub }}</div>
            </div>
        </div>

        <div class="sem">{{ $badge }}</div>

        @if(isset($sidebar))
            {{ $sidebar }}
        @else
            @if($role === 'guru')
                <x-sidebar.guru />
            @elseif($role === 'kepala')
                <x-sidebar.kepala />
            @else
                <x-sidebar.operator />
            @endif
        @endif
    </aside>

    <main class="main">
        <header class="top">
            <button class="ham" id="hamburgerBtn" type="button" aria-label="Buka/tutup menu" onclick="window.toggleSidebar ? window.toggleSidebar() : null">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>

            @if(isset($topbarLeft))
                {{ $topbarLeft }}
            @elseif($role === 'kepala')
                <span class="ro">HANYA LIHAT</span>
            @endif

            <div class="user">
                <span>{{ $displayName }}</span>
                <div class="ava">{{ $userInitials }}</div>
            </div>
        </header>

        <div class="crumb">
            @if(isset($breadcrumb))
                {{ $breadcrumb }}
            @elseif(!empty($breadcrumbs))
                @foreach($breadcrumbs as $label => $url)
                    @if(!$loop->last)
                        <a href="{{ $url }}">{{ $label }}</a> /
                    @else
                        <span>{{ $label }}</span>
                    @endif
                @endforeach
            @else
                <a href="#">Home</a> / <span>Dashboard</span>
            @endif
        </div>

        <div class="body">
            {{ $slot }}
        </div>

        <footer class="foot">
            <div>{{ $brand }} Versi 1.0 © {{ date('Y') }}</div>
            <div>Bukan aplikasi resmi Kementerian Agama</div>
        </footer>
    </main>
</div>
<script>
window.toggleSidebar = function() {
    var sidebar = document.getElementById('appSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;
    if (window.innerWidth <= 768) {
        var isOpen = sidebar.classList.toggle('open');
        if (backdrop) backdrop.classList.toggle('active', isOpen);
    } else {
        var isCollapsed = sidebar.classList.toggle('collapsed');
        try { localStorage.setItem('sidebar_collapsed', isCollapsed ? '1' : '0'); } catch(e) {}
    }
};

if (window.innerWidth > 768) {
    try {
        if (localStorage.getItem('sidebar_collapsed') === '1') {
            document.getElementById('appSidebar')?.classList.add('collapsed');
        }
    } catch(e) {}
}
</script>
</body>
</html>
