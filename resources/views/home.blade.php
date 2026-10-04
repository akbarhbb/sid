@extends('layouts.app')

@section('title', 'Portal Warga Pengasinan')

@section('content')
<section class="hero">
    <div class="container hero-grid">
        <div>
            <div class="eyebrow">Portal warga Pengasinan, Sawangan</div>
            <h1>Lingkungan nyaman,<br><span>warga saling terhubung.</span></h1>
            <p class="hero-copy">Pusat informasi dan layanan lingkungan RT 06 / RW 09 Pengasinan dalam satu tempat. Cari pengumuman, ajukan kebutuhan, dan ikuti kabar kas warga dengan lebih mudah.</p>
            <div class="hero-buttons"><a class="button button-primary button-large" href="{{ route('register') }}">Mulai layanan warga <span aria-hidden="true">↗</span></a><a class="button button-outline button-large" href="{{ route('announcements') }}">Lihat pengumuman</a></div>
            <div class="hero-meta"><div class="avatar-stack" aria-hidden="true"><span class="avatar">AR</span><span class="avatar">DN</span><span class="avatar">MH</span><span class="avatar">+W</span></div><span>Ruang bersama untuk warga dan pengurus</span></div>
        </div>
        <div class="hero-art" aria-label="Ilustrasi kawasan permukiman">
            <div class="art-header"><span class="art-label">KABAR LINGKUNGAN</span><span class="art-date">{{ now()->translatedFormat('l, d F Y') }}</span></div>
            <div class="art-image"><svg class="neighborhood-svg" viewBox="0 0 600 300" role="img" aria-label="Rumah dan pepohonan di lingkungan warga" preserveAspectRatio="xMidYMid slice"><rect width="600" height="300" fill="#dce8f5"/><circle cx="484" cy="58" r="30" fill="#f7d89d"/><path d="M0 212Q110 166 218 208T430 202T600 196V300H0Z" fill="#a9c5a5"/><path d="M0 240Q145 206 276 239T600 224V300H0Z" fill="#789b7c"/><path d="M53 139 132 85l79 54v98H53Z" fill="#f8f5ef"/><path d="m42 143 90-70 91 70-9 12-82-62-81 62Z" fill="#b95450"/><path d="M82 164h34v38H82zm61 0h34v38h-34z" fill="#89aac7"/><path d="M117 192h30v45h-30Z" fill="#937456"/><path d="m287 160 71-49 70 49v82H287Z" fill="#fffaf0"/><path d="m278 162 80-62 80 62-9 11-71-54-70 54Z" fill="#536f98"/><path d="M310 179h29v31h-29zm54 0h29v31h-29z" fill="#8eb1c9"/><path d="M339 204h28v38h-28Z" fill="#9b7457"/><path d="M0 270q170-30 300 0t300 0v30H0Z" fill="#d9dfdb"/><path d="M0 286q180-17 310 0t290 0" fill="none" stroke="#fff" stroke-width="4" stroke-dasharray="16 15"/><g fill="#4e775d"><circle cx="247" cy="177" r="22"/><circle cx="253" cy="151" r="19"/><rect x="245" y="177" width="7" height="43" fill="#806746"/><circle cx="450" cy="182" r="24"/><circle cx="461" cy="153" r="21"/><rect x="451" y="183" width="7" height="40" fill="#806746"/></g></svg></div>
            <div class="art-card"><div class="art-card-top"><span class="art-label">PENGUMUMAN TERBARU</span><span class="live-pill">Info warga</span></div><h3>{{ $announcements->first()?->title ?? 'Selamat datang di portal warga' }}</h3><p>{{ $announcements->first()?->published_at?->translatedFormat('d F Y') ?? 'Informasi lingkungan dan layanan kini tersedia di satu tempat.' }}</p></div>
        </div>
    </div>
</section>

<section class="section section-white">
    <div class="container">
        <div class="section-head"><div><div class="eyebrow">Tetap terinformasi</div><h2 class="section-title">Kabar lingkungan</h2><p class="section-sub">Informasi terbaru dari pengurus untuk seluruh warga.</p></div><a class="text-link" href="{{ route('announcements') }}">Semua pengumuman ↗</a></div>
        <div class="announcement-grid">
            @forelse ($announcements as $announcement)
                <article class="announcement-card"><div class="announcement-top"><span class="tag {{ $announcement->is_emergency ? 'tag-emergency' : '' }}">{{ $announcement->is_emergency ? 'Penting' : ucfirst($announcement->category) }}</span><span class="card-date">{{ $announcement->published_at?->translatedFormat('d M Y') }}</span></div><h3>{{ $announcement->title }}</h3><p>{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 110) }}</p></article>
            @empty
                <article class="announcement-card"><div class="announcement-top"><span class="tag">Selamat datang</span><span class="card-date">Portal warga</span></div><h3>Informasi warga segera hadir di sini</h3><p>Pengurus dapat membagikan kabar ronda, kerja bakti, iuran, kegiatan, dan keadaan darurat melalui portal ini.</p></article>
                <article class="announcement-card"><div class="announcement-top"><span class="tag">Layanan</span><span class="card-date">Mudah diakses</span></div><h3>Ajukan kebutuhan dari ponsel</h3><p>Pengajuan surat, laporan, izin, dan peminjaman fasilitas dirancang agar lebih mudah diikuti.</p></article>
                <article class="announcement-card"><div class="announcement-top"><span class="tag">Transparan</span><span class="card-date">Kas warga</span></div><h3>Ringkasan keuangan lingkungan</h3><p>Catatan kas dan iuran ditampilkan dengan ringkas untuk membantu warga mengikuti pengelolaan dana.</p></article>
            @endforelse
        </div>
    </div>
</section>

<section class="section" id="layanan"><div class="container"><div class="section-head"><div><div class="eyebrow">Akses cepat</div><h2 class="section-title">Apa yang ingin kamu lakukan?</h2><p class="section-sub">Pilih layanan, ikuti langkahnya, dan pantau kabar lingkungan.</p></div></div>
    <div class="quick-grid">
        <a class="quick-card" href="{{ route('register') }}"><span class="quick-icon"><svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7"/><path d="M14 3v6h5M9 14h6m-6 4h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><h3>Surat pengantar</h3><p>Ajukan surat dan pantau prosesnya.</p></a>
        <a class="quick-card" href="{{ route('register') }}"><span class="quick-icon red"><svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M12 21s8-4 8-11V5l-8-3-8 3v5c0 7 8 11 8 11Z" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v5m0 3h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h3>Lapor masalah</h3><p>Sampaikan keluhan atau kejadian.</p></a>
        <a class="quick-card" href="{{ route('home') }}#keuangan"><span class="quick-icon"><svg class="icon" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><h3>Kas & iuran</h3><p>Lihat ringkasan keuangan warga.</p></a>
        <a class="quick-card" href="{{ route('register') }}"><span class="quick-icon red"><svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" stroke="currentColor" stroke-width="1.7"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><h3>Keamanan lingkungan</h3><p>Informasi keamanan dan akses CCTV.</p></a>
    </div>
</div></section>

<section class="section section-white" id="keuangan"><div class="container"><div class="section-head"><div><div class="eyebrow">Keterbukaan warga</div><h2 class="section-title">Ringkasan kas RT</h2><p class="section-sub">Transaksi yang ditampilkan sudah disetujui pengurus.</p></div></div><div class="panel" style="margin-bottom:16px"><span class="muted" style="font-size:12px">Saldo publik</span><div style="font:800 28px Manrope,sans-serif;color:#193b67;margin-top:8px">Rp {{ number_format($balance, 0, ',', '.') }}</div></div><div class="panel table-wrap"><table class="table"><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jumlah</th></tr></thead><tbody>@forelse ($transactions as $transaction)<tr><td>{{ $transaction->occurred_at->translatedFormat('d M Y') }}</td><td>{{ $transaction->description }}</td><td>{{ ucfirst($transaction->organization) }} · {{ $transaction->category }}</td><td style="color:{{ $transaction->type === 'pemasukan' ? '#237a5b' : '#b6353e' }}">{{ $transaction->type === 'pemasukan' ? '+' : '−' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="4" class="muted">Transaksi kas publik belum dicatat.</td></tr>@endforelse</tbody></table></div></div></section>
<section class="stats-band"><div class="container stats-grid"><div class="stat"><strong>Terbuka</strong><span>Informasi untuk warga</span></div><div class="stat"><strong>Praktis</strong><span>Layanan dari ponsel</span></div><div class="stat"><strong>Tercatat</strong><span>Proses lebih tertata</span></div><div class="stat"><strong>Bersama</strong><span>Warga dan pengurus</span></div></div></section>
<span id="keamanan"></span>
@endsection
