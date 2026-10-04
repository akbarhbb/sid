@extends('layouts.app')
@section('title', 'Status Pembayaran')
@section('content')
<section class="page"><div class="container" style="max-width:650px"><div class="panel"><div class="eyebrow">Pembayaran iuran</div><h1 style="font:800 30px Manrope,sans-serif;margin:12px 0">{{ ucfirst($paymentTransaction->status) }}</h1><p>Referensi <strong>{{ $paymentTransaction->reference }}</strong></p><p>Jumlah <strong>Rp {{ number_format($paymentTransaction->amount,0,',','.') }}</strong></p><p>Metode <strong>{{ $paymentTransaction->method }}</strong></p>@if($paymentTransaction->status === 'pending')<p class="muted">Status diperbarui otomatis setelah provider mengirim konfirmasi pembayaran.</p>@endif<a class="button button-primary" href="{{ route('invoices.mine') }}">Kembali ke tagihan</a></div></div></section>
@endsection
