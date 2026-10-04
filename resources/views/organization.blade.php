@extends('layouts.app')

@section('title', 'Struktur Organisasi')

@section('content')
<section class="page">
    <div class="container">
        <div class="page-heading">
            <div class="eyebrow">Kepengurusan lingkungan</div>
            <h1>Struktur organisasi</h1>
            <p>Pengurus RT 06 / RW 09 Pengasinan, serta DKM dan Karang Taruna.</p>
        </div>

        @php
            $labels = ['rt' => 'RT 06 / RW 09', 'dkm' => 'DKM', 'karang_taruna' => 'Karang Taruna'];
            $initials = fn ($name) => collect(explode(' ', $name))->take(2)->map(fn ($n) => strtoupper(substr($n, 0, 1)))->implode('');
        @endphp

        @if ($members->isEmpty())
            <div class="panel">
                <p class="muted">Struktur organisasi belum diisi pengurus. Data akan tampil di sini setelah pengurus menambahkan anggota.</p>
            </div>
        @else
            @foreach (['rt', 'dkm', 'karang_taruna'] as $type)
                @if ($members->has($type))
                    <div class="panel" style="margin-bottom:20px">
                        <h2 class="panel-title">{{ $labels[$type] }}</h2>
                        <div class="announcement-grid">
                            @foreach ($members->get($type) as $member)
                                <article class="announcement-card" style="text-align:center">
                                    <div style="margin:0 auto 14px">
                                        @if ($member->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($member->photo_path))
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($member->photo_path) }}" alt="{{ $member->name }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover">
                                        @else
                                            <span class="avatar" style="width:72px;height:72px;font-size:22px;margin:0">{{ $initials($member->name) }}</span>
                                        @endif
                                    </div>
                                    <h3>{{ $member->name }}</h3>
                                    <p style="font-weight:700;color:#315f98;margin-bottom:4px">{{ $member->position }}</p>
                                    @if ($member->term_starts_at || $member->term_ends_at)
                                        <p class="muted" style="font-size:11px">Masa jabatan: {{ $member->term_starts_at?->translatedFormat('M Y') ?? '...' }} – {{ $member->term_ends_at?->translatedFormat('M Y') ?? '...' }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</section>
@endsection
