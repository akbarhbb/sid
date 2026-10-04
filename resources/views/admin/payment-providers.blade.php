@extends('layouts.app')
@section('title', 'Konfigurasi Payment Gateway')
@section('content')
<section class="page"><div class="container">
    <div class="page-heading"><div class="eyebrow">Kas & iuran</div><h1>Payment gateway</h1><p>Kelola beberapa penyedia secara terpisah. Pilih driver, metode pembayaran, dan kredensial sesuai kebutuhan wilayah.</p></div>
    @if (session('status'))<div class="alert">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="error-list">{{ $errors->first() }}</div>@endif
    <div class="panel" style="margin-bottom:20px"><h2 class="panel-title">Penyedia terpasang</h2>
        <div class="table-wrap"><table class="table"><thead><tr><th>Nama</th><th>Driver</th><th>Metode</th><th>Mode</th><th>Status</th><th>Pengaturan</th></tr></thead><tbody>
            @forelse ($providers as $provider)
                <tr><td><strong>{{ $provider->name }}</strong>@if ($provider->is_default)<div class="muted" style="font-size:10px;margin-top:4px">Default</div>@endif</td><td><code>{{ $provider->driver }}</code></td><td>{{ implode(', ', $provider->supported_methods ?? []) }}</td><td>{{ $provider->environment === 'production' ? 'Produksi' : 'Sandbox' }}</td><td><span class="status" style="{{ $provider->is_active ? '' : 'background:#f0f2f5;color:#64748b' }}">{{ $provider->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td><details><summary class="text-link" style="cursor:pointer">Edit</summary><form method="post" action="{{ route('admin.payment-providers.update', $provider) }}" style="min-width:270px;padding-top:12px">@csrf @method('PATCH')
                    <div class="field"><label>Nama tampilan</label><input name="name" value="{{ $provider->name }}" required></div><div class="field"><label>Driver / adapter</label><input name="driver" value="{{ $provider->driver }}" pattern="[A-Za-z0-9._-]+" required></div>
                    <div class="field"><label>Mode</label><select name="environment"><option value="sandbox" @selected($provider->environment === 'sandbox')>Sandbox</option><option value="production" @selected($provider->environment === 'production')>Produksi</option></select></div>
                    <div class="field"><label>Metode, pisahkan dengan koma</label><input name="supported_methods_csv" value="{{ implode(', ', $provider->supported_methods ?? []) }}" data-methods-input></div>
                    @foreach ($provider->supported_methods ?? [] as $method)<input type="hidden" name="supported_methods[]" value="{{ $method }}" data-method-hidden>@endforeach
                    <div class="field"><label>Kredensial JSON <span class="muted">(kosongkan agar tidak berubah)</span></label><textarea name="credentials" placeholder="{ &quot;api_key&quot;: &quot;...&quot; }"></textarea></div>
                    <label class="check-row"><input type="checkbox" name="is_active" value="1" @checked($provider->is_active)><span>Aktifkan penyedia</span></label><label class="check-row"><input type="checkbox" name="is_default" value="1" @checked($provider->is_default)><span>Jadikan default</span></label><button class="button button-primary" type="submit">Simpan konfigurasi</button>
                </form><form method="post" action="{{ route('admin.payment-providers.destroy', $provider) }}" style="margin-top:10px">@csrf @method('DELETE')<button class="button button-outline" type="submit">Hapus penyedia</button></form></details></td></tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada penyedia. Tambahkan provider pertama di bawah.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
    <div class="panel"><h2 class="panel-title">Tambah penyedia pembayaran</h2><p class="muted" style="font-size:12px;line-height:1.6">Tidak dikunci ke satu gateway. Isi driver adapter dan metode yang didukung. Kredensial disimpan terenkripsi di database.</p>
        <form method="post" action="{{ route('admin.payment-providers.store') }}" id="provider-form">@csrf
            <p class="muted" style="font-size:12px">Driver <code>generic_http</code> mendukung endpoint checkout JSON dan verifikasi webhook HMAC. Nama field webhook dapat dipetakan lewat JSON kredensial provider.</p>
            <div class="form-grid"><div class="field"><label for="name">Nama provider</label><input id="name" name="name" placeholder="Nama penyedia" required></div><div class="field"><label for="driver">Driver / adapter</label><input id="driver" name="driver" placeholder="generic_http atau nama adapter" pattern="[A-Za-z0-9._-]+" required></div>
            <div class="field"><label for="environment">Mode</label><select id="environment" name="environment"><option value="sandbox">Sandbox</option><option value="production">Produksi</option></select></div><div class="field"><label for="supported_methods_csv">Metode pembayaran</label><input id="supported_methods_csv" name="supported_methods_csv" placeholder="QRIS, VA, e-wallet" data-methods-input required></div></div>
            <div id="method-hidden-inputs"></div><div class="field"><label for="credentials">Kredensial JSON</label><textarea id="credentials" name="credentials" placeholder="{ &quot;checkout_endpoint&quot;: &quot;https://...&quot;, &quot;api_key&quot;: &quot;...&quot;, &quot;webhook_secret&quot;: &quot;...&quot;, &quot;webhook_signature_header&quot;: &quot;X-Signature&quot;, &quot;webhook_reference_path&quot;: &quot;reference&quot;, &quot;webhook_amount_path&quot;: &quot;amount&quot;, &quot;webhook_currency_path&quot;: &quot;currency&quot; }" spellcheck="false"></textarea></div>
            <label class="check-row"><input type="checkbox" name="is_active" value="1"><span>Aktifkan setelah disimpan</span></label><label class="check-row"><input type="checkbox" name="is_default" value="1"><span>Jadikan penyedia default</span></label><button class="button button-primary button-large" type="submit">Tambah provider</button>
        </form>
    </div>
</div></section>
<script>
document.querySelectorAll('[data-methods-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const form = input.closest('form');
        const container = form.querySelector('#method-hidden-inputs') || form;
        container.querySelectorAll('[data-method-hidden]').forEach((hidden) => hidden.remove());
        input.value.split(',').map((method) => method.trim()).filter(Boolean).forEach((method) => {
            const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'supported_methods[]'; hidden.value = method; hidden.dataset.methodHidden = 'true'; container.appendChild(hidden);
        });
    });
});
document.querySelectorAll('[data-methods-input]').forEach((input) => input.dispatchEvent(new Event('change')));
</script>
@endsection
