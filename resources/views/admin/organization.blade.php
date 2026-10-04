@extends('layouts.app')

@section('title', 'Struktur Organisasi')

@section('content')
<section class="page">
    <div class="container">
        <div class="page-heading">
            <div class="eyebrow">Kepengurusan lingkungan</div>
            <h1>Struktur organisasi</h1>
            <p>Tambah dan kelola pengurus RT, DKM, dan Karang Taruna beserta masa jabatannya.</p>
        </div>

        @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="error-list">{{ $errors->first() }}</div>@endif

        <div class="panel" style="margin-bottom:20px">
            <h2 class="panel-title">Tambah anggota</h2>
            <form method="post" action="{{ route('admin.organization.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="field">
                        <label>Organisasi</label>
                        <select name="organization_type" required>
                            <option value="rt">RT 06 / RW 09</option>
                            <option value="dkm">DKM</option>
                            <option value="karang_taruna">Karang Taruna</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Nama lengkap</label>
                        <input name="name" required maxlength="120">
                    </div>
                    <div class="field">
                        <label>Jabatan</label>
                        <input name="position" required maxlength="120" placeholder="mis. Ketua RT, Bendahara, Sekretaris">
                    </div>
                    <div class="field">
                        <label>Foto (opsional)</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png">
                    </div>
                    <div class="field">
                        <label>Masa jabatan mulai</label>
                        <input type="date" name="term_starts_at">
                    </div>
                    <div class="field">
                        <label>Masa jabatan selesai</label>
                        <input type="date" name="term_ends_at">
                    </div>
                    <div class="field">
                        <label>Urutan tampil</label>
                        <input type="number" name="display_order" value="0" min="0" max="999">
                    </div>
                </div>
                <button class="button button-primary">Tambah anggota</button>
            </form>
        </div>

        <div class="panel table-wrap">
            <h2 class="panel-title">Daftar pengurus</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Organisasi</th>
                        <th>Masa jabatan</th>
                        <th>Urutan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td>{{ $member->name }}</td>
                            <td>{{ $member->position }}</td>
                            <td>{{ match($member->organization_type) { 'dkm' => 'DKM', 'karang_taruna' => 'Karang Taruna', default => 'RT 06 / RW 09' } }}</td>
                            <td>{{ $member->term_starts_at?->translatedFormat('M Y') ?? '-' }} – {{ $member->term_ends_at?->translatedFormat('M Y') ?? '-' }}</td>
                            <td>{{ $member->display_order }}</td>
                            <td>
                                <form method="post" action="{{ route('admin.organization.destroy', $member) }}" style="display:inline" onsubmit="return confirm('Hapus anggota ini?')">
                                    @csrf @method('DELETE')
                                    <button class="button button-outline" style="padding:8px 12px;font-size:11px">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Belum ada data pengurus.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
