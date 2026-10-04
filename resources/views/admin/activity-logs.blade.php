@extends('layouts.app')
@section('title', 'Log Aktivitas')
@section('content')
<section class="page"><div class="container"><div class="page-heading"><div class="eyebrow">Audit dan akuntabilitas</div><h1>Log aktivitas</h1><p>Riwayat aksi pengurus dan perubahan data untuk wilayah ini.</p></div><div class="panel table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Subjek</th><th>Detail</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->created_at->translatedFormat('d M Y H:i') }}</td><td>{{ $log->user?->name ?: 'Sistem' }}</td><td>{{ $log->action }}</td><td>{{ class_basename($log->subject_type ?: '—') }} {{ $log->subject_id }}</td><td>{{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE) }}</td></tr>@empty<tr><td colspan="5">Belum ada catatan aktivitas.</td></tr>@endforelse</tbody></table></div><div style="margin-top:18px">{{ $logs->links() }}</div></div></section>
@endsection
