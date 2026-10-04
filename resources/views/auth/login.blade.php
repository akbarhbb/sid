@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
<section class="auth-wrap"><div class="container" style="display:grid;place-items:center"><div class="auth-card"><div class="eyebrow">Akun warga</div><h1>Selamat datang kembali</h1><p>Masuk untuk melihat pengajuan layanan, informasi warga, dan aktivitas lingkungan.</p>
    @if (session('status'))<div class="alert">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="error-list">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('login.store') }}">@csrf
        <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></div>
        <div class="field"><label for="password">Kata sandi</label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
        <label class="check-row"><input type="checkbox" name="remember" value="1"><span>Ingat saya di perangkat ini</span></label>
        <button class="button button-primary button-large" style="width:100%" type="submit">Masuk ke akun</button>
    </form><p style="margin:18px 0 0;text-align:center">Belum terdaftar? <a class="text-link" href="{{ route('register') }}">Daftar sebagai warga</a></p>
</div></div></section>
@endsection
