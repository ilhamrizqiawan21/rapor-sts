@props([
    'title' => 'Rapor STS',
    'breadcrumbs' => [],
    'userRole' => 'operator',
    'userName' => 'Admin MTsS Al-Ihsan',
    'userInitials' => 'AD',
    'brandName' => 'Rapor STS',
    'brandSub' => 'MTsS Al-Ihsan',
    'semesterBadge' => 'SEMESTER GANJIL 2026/2027',
    'topbarColor' => null,
])

<!doctype html>
<html lang="id" @if($topbarColor) style="--topbar: {{ $topbarColor }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Rapor STS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app">
    <aside class="side" id="appSidebar">
        <div class="brand">
            <div class="logo">RS</div>
            <div>
                <div class="bname">{{ $brandName }}</div>
                <div class="bsub">{{ $brandSub }}</div>
            </div>
        </div>

        <div class="sem">{{ $semesterBadge }}</div>

        @if(isset($sidebar))
            {{ $sidebar }}
        @else
            @if($userRole === 'guru')
                <x-sidebar.guru />
            @elseif($userRole === 'kepala')
                <x-sidebar.kepala />
            @else
                <x-sidebar.operator />
            @endif
        @endif
    </aside>

    <main class="main">
        <header class="top">
            <button class="ham" type="button" aria-label="Buka/tutup menu" onclick="document.getElementById('appSidebar').classList.toggle('open')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>

            @if(isset($topbarLeft))
                {{ $topbarLeft }}
            @endif

            <div class="user">
                <span>{{ $userName }}</span>
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
            <div>{{ $brandName }} Versi 1.0 © {{ date('Y') }}</div>
            <div>Bukan aplikasi resmi Kementerian Agama</div>
        </footer>
    </main>
</div>
</body>
</html>
