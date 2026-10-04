@extends('layouts.app')
@section('title', 'Hasil Pemilihan')
@section('content')
<section class="page"><div class="container" style="max-width:850px"><div class="page-heading"><div class="eyebrow">Hasil terverifikasi</div><h1>{{ $electionRecord->title }}</h1><p>Hasil diterbitkan {{ \Illuminate\Support\Carbon::parse($electionRecord->results_published_at)->translatedFormat('d F Y H:i') }}.</p></div><div class="panel table-wrap"><table class="table"><thead><tr><th>Peringkat</th><th>Calon</th><th>Visi</th><th>Suara</th></tr></thead><tbody>@foreach($results as $index => $result)<tr><td>{{ $index + 1 }}</td><td><strong>{{ $result->name }}</strong></td><td>{{ $result->vision ?: '—' }}</td><td><strong>{{ $result->votes }}</strong></td></tr>@endforeach</tbody></table><p class="muted" style="font-size:11px;margin:16px 0 0">Pilihan suara bersifat rahasia. Halaman ini tidak menghubungkan suara dengan pemilih.</p></div></div></section>
@endsection
