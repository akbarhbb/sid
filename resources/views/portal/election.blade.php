@extends('layouts.app')
@section('title', 'Pemilihan Ketua RT')
@section('content')
<section class="page"><div class="container"><div class="page-heading"><div class="eyebrow">Demokrasi warga</div><h1>Pemilihan Ketua RT</h1><p>Informasi calon dan tahapan pemilihan wilayah.</p></div>
    @if (session('status'))<div class="alert">{{ session('status') }}</div>@endif
    @if (! $election)<div class="panel"><h2 class="panel-title">Belum ada pemilihan aktif</h2><p class="muted">Informasi pemilihan akan tampil setelah panitia membuka tahapan.</p></div>@else
        <div class="panel" style="margin-bottom:20px"><span class="tag">Tahap: {{ ucfirst($election->status) }}</span><h2 class="panel-title" style="margin-top:14px">{{ $election->title }}</h2><p class="muted">Pemungutan suara: {{ $election->voting_opens_at }} — {{ $election->voting_closes_at }}</p></div>
        <div class="quick-grid" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr))">@forelse ($candidates as $candidate)<article class="panel"><span class="tag">Calon</span><h2 class="panel-title" style="margin:13px 0 8px">{{ $candidate->name }}</h2><p>{{ $candidate->profile }}</p><h3 style="font:700 13px Manrope,sans-serif">Visi</h3><p class="muted">{{ $candidate->vision ?: 'Belum diisi' }}</p><h3 style="font:700 13px Manrope,sans-serif">Misi</h3><p class="muted">{{ $candidate->mission ?: 'Belum diisi' }}</p>
            @if ($election->status === 'closed' && $election->results_published_at)<strong>Hasil terverifikasi: {{ \Illuminate\Support\Facades\DB::table('election_ballots')->where('election_id', $election->id)->where('candidate_id', $candidate->id)->count() }} suara</strong>@elseif ($election->status === 'voting' && $eligibility?->verified_at && ! $eligibility?->voted_at)<form method="post" action="{{ route('elections.vote', $election->id) }}">@csrf<label class="check-row"><input type="checkbox" name="election_consent" value="1" required><span>Saya menyetujui aturan dan pemrosesan data pemilih.</span></label><button class="button button-primary" name="candidate_id" value="{{ $candidate->id }}" type="submit">Pilih calon ini</button></form>@endif
        </article>@empty<div class="panel"><p class="muted">Profil calon belum diterbitkan.</p></div>@endforelse</div>
        @if ($election->status === 'registration' && auth()->user()->is_adult && ! $eligibility?->consented_at)<form method="post" action="{{ route('elections.consent',$election->id) }}" class="panel" style="margin-top:18px">@csrf<label class="check-row"><input type="checkbox" name="election_consent" value="1" required><span>Saya bersedia menjadi pemilih dalam pemilihan ini dan menyetujui verifikasi kelayakan pemilih.</span></label><button class="button button-primary">Kirim persetujuan pemilih</button></form>@endif
        @if ($election->status === 'voting' && ! $eligibility?->verified_at)<p class="muted" style="margin-top:18px">Pemilih belum diverifikasi panitia.</p>@endif
        @if ($election->status === 'closed' && $election->results_published_at)<a class="button button-primary" href="{{ route('elections.public-results',$election->id) }}">Lihat hasil terverifikasi</a>@endif
        @if ($election->status === 'voting' && $eligibility?->voted_at)<div class="alert" style="margin-top:18px">Suara kamu sudah tercatat. Pilihan tetap rahasia.</div>@endif
    @endif
</div></section>
@endsection
