@extends('layouts.app')
@section('title', 'Pengajuan Saya')
@section('content')
<section class="page"><div class="container"><div class="page-heading"><div class="eyebrow">Layanan warga</div><h1>Pengajuan saya</h1><p>Pantau perkembangan pengajuan ke pengurus.</p></div>
    @if (session('status'))<div class="alert">{{ session('status') }}</div>@endif
    <div class="panel table-wrap"><table class="table"><thead><tr><th>Nomor</th><th>Jenis</th><th>Keperluan</th><th>Status</th><th>Catatan pengurus</th></tr></thead><tbody>
        @forelse ($requests as $serviceRequest)<tr><td><strong>{{ $serviceRequest->reference }}</strong><div class="muted">{{ $serviceRequest->created_at->translatedFormat('d M Y') }}</div></td><td>{{ str_replace('_', ' ', ucfirst($serviceRequest->type)) }}</td><td>{{ $serviceRequest->subject }}</td><td><span class="tag">{{ ucfirst($serviceRequest->status) }}</span></td><td>{{ $serviceRequest->staff_note ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="muted">Belum ada pengajuan. <a class="text-link" href="{{ route('service-requests.create') }}">Ajukan layanan</a></td></tr>@endforelse
    </tbody></table></div><div style="margin-top:20px">{{ $requests->links() }}</div>
</div></section>
@endsection
