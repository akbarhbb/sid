@extends('layouts.app')
@section('title', 'Ajukan Layanan')
@section('content')
<section class="page"><div class="container" style="max-width:760px"><div class="page-heading"><div class="eyebrow">Layanan warga</div><h1>Ajukan kebutuhan</h1><p>Pengajuan akan diteruskan ke pengurus. Masuk menggunakan akun yang sudah disetujui.</p></div>
    @if ($errors->any())<div class="error-list">{{ $errors->first() }}</div>@endif
    <form class="panel" method="post" action="{{ route('service-requests.store') }}">@csrf
        <div class="field"><label for="type">Jenis layanan</label><select id="type" name="type" required><option value="">Pilih jenis layanan</option><option value="surat_pengantar">Surat pengantar</option><option value="keluhan">Keluhan atau laporan</option><option value="izin_kegiatan">Izin kegiatan</option><option value="peminjaman_fasilitas">Peminjaman fasilitas</option></select></div>
        <div class="field"><label for="subject">Keperluan</label><input id="subject" name="subject" value="{{ old('subject') }}" maxlength="160" required></div>
        <div class="field"><label for="description">Keterangan</label><textarea id="description" name="description" maxlength="5000" required>{{ old('description') }}</textarea></div>
        <button class="button button-primary button-large" type="submit">Kirim pengajuan</button> <a class="button button-outline" href="{{ route('service-requests.mine') }}">Lihat pengajuan saya</a>
    </form>
</div></section>
@endsection
