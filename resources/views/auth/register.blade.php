@extends('layouts.app')
@section('title', 'Daftar Warga')
@section('content')
<section class="auth-wrap"><div class="container" style="display:grid;place-items:center"><div class="auth-card"><div class="eyebrow">Pendaftaran warga</div><h1>Bergabung ke portal warga</h1><p>Isi data dasar. Pengurus akan memeriksa dan menyetujui akun sebelum layanan dapat digunakan.</p>
    @if ($errors->any())<div class="error-list">Periksa kembali data yang dimasukkan. {{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('register.store') }}">@csrf
        <div class="field"><label for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required></div>
        <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></div>
        <div class="form-grid"><div class="field"><label for="phone">Nomor HP</label><input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" required></div><div class="field"><label for="house_number">Nomor rumah <span class="muted">(opsional)</span></label><input id="house_number" name="house_number" value="{{ old('house_number') }}"></div></div>
        <div class="field"><label for="address">Alamat</label><input id="address" name="address" value="{{ old('address') }}" autocomplete="street-address" required></div>
        <div class="form-grid"><div class="field"><label for="password">Kata sandi</label><input id="password" type="password" name="password" autocomplete="new-password" required></div><div class="field"><label for="password_confirmation">Ulangi kata sandi</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div></div>
        <label class="check-row"><input type="checkbox" name="consent" value="1" required><span>Saya menyetujui penggunaan data untuk verifikasi warga dan layanan lingkungan. Saya dapat meminta koreksi data kepada pengurus.</span></label>
        <button class="button button-primary button-large" style="width:100%" type="submit">Kirim pendaftaran</button>
    </form><p style="margin:18px 0 0;text-align:center">Sudah punya akun? <a class="text-link" href="{{ route('login') }}">Masuk</a></p>
</div></div></section>
@endsection
