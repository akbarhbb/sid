@extends('layouts.app')
@section('title', 'Pengumuman Warga')
@section('content')
<section class="page"><div class="container"><div class="page-heading"><div class="eyebrow">Informasi lingkungan</div><h1>Pengumuman warga</h1><p>Kabar, agenda, dan informasi terbaru dari pengurus lingkungan.</p></div>
    <div class="announcement-grid">
        @forelse ($announcements as $announcement)
            <article class="announcement-card"><div class="announcement-top"><span class="tag {{ $announcement->is_emergency ? 'tag-emergency' : '' }}">{{ $announcement->is_emergency ? 'Penting' : ucfirst($announcement->category) }}</span><span class="card-date">{{ $announcement->published_at?->translatedFormat('d M Y') }}</span></div><h3>{{ $announcement->title }}</h3><p>{{ $announcement->body }}</p></article>
        @empty
            <article class="announcement-card"><span class="tag">Belum ada pengumuman</span><h3>Informasi baru akan tampil di sini</h3><p>Pengurus dapat menerbitkan pengumuman melalui panel pengelolaan.</p></article>
        @endforelse
    </div>
    <div style="margin-top:22px">{{ $announcements->links() }}</div>
</div></section>
@endsection
