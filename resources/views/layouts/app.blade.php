<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#16345d">
    <title>@yield('title', 'Ruang Warga') · {{ config('app.name', 'Ruang Warga') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="topbar">
        <div class="container topbar-inner">
            <div class="topbar-note"><span class="top-dot"></span><span>Portal layanan warga · Pengasinan, Sawangan</span></div>
            <div class="top-links"><span>Pelayanan warga, lebih dekat</span><span>Bahasa Indonesia</span></div>
        </div>
    </div>
    <header class="navbar">
        <div class="container nav-inner">
            <a class="brand" href="{{ route('home') }}" aria-label="Ruang Warga, beranda">
                <span class="brand-mark"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M2 10 12 2l10 8" stroke="#cf3947" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                <span><span class="brand-name">Ruang Warga</span><span class="brand-sub">RT 06 / RW 09 Pengasinan, Depok</span></span>
            </a>
            <nav class="nav-links" aria-label="Navigasi utama">
                <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
                <a href="{{ route('announcements') }}">Informasi</a>
                <a href="{{ route('organization.public') }}">Struktur</a>
                @auth<a href="{{ route('service-requests.create') }}">Layanan warga</a>@else<a href="{{ route('register') }}">Layanan warga</a>@endauth
                <a href="{{ route('home') }}#keuangan">Keuangan</a>
                @auth<a href="{{ route('cctv.index') }}">Keamanan</a>@else<a href="{{ route('register') }}">Keamanan</a>@endauth
                @auth<a href="{{ route('elections.show') }}">Pemilihan</a>@endauth
                @auth<a href="{{ route('activities.index') }}">Agenda</a><a href="{{ route('facilities.index') }}">Fasilitas</a><a href="{{ route('profile.edit') }}">Data saya</a><a href="{{ route('invoices.mine') }}">Iuran saya</a>@endauth
            </nav>
            <div class="nav-actions">
                @auth
                    @if (in_array(auth()->user()->role, ['superadmin', 'admin_rt'], true))
                        <a class="button button-outline" href="{{ route('admin.dashboard') }}">Panel pengurus</a>
                    @endif
                    <form action="{{ route('logout') }}" method="post">@csrf<button class="button button-primary" type="submit">Keluar</button></form>
                @else
                    <a class="button button-outline" href="{{ route('login') }}">Masuk</a>
                    <a class="button button-primary" href="{{ route('register') }}">Daftar warga <span aria-hidden="true">↗</span></a>
                @endauth
            </div>
        </div>
    </header>
    <main>@yield('content')</main>
    <footer class="footer">
        <div class="container footer-inner"><a href="{{ route('home') }}" class="footer-brand">Ruang Warga</a><span>Informasi lingkungan yang terbuka, layanan yang lebih mudah.</span><span>Pengasinan · Sawangan</span></div>
    </footer>
</body>
</html>
