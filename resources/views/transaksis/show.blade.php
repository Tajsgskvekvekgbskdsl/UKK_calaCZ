@extends('layouts.app')

@section('title', 'Detail Transaksi')
@section('page-title', 'Detail Transaksi')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Detail Transaksi #{{ $transaksi->id_transaksi }}</div>
            <div class="card-body">
                <table class="table table-bordered mb-3">
                    <tbody>
                        <tr><th style="width:200px;">Anggota</th>
                            <td>{{ $transaksi->anggota?->nama ?? '-' }}
                                <small class="text-muted d-block">{{ $transaksi->anggota?->kelas }} · NIS {{ $transaksi->anggota?->nis }}</small>
                            </td></tr>
                        <tr><th>Akun User</th><td>{{ $transaksi->anggota?->user?->username ?? '-' }}</td></tr>
                        <tr><th>Buku</th><td>{{ $transaksi->buku?->judul_buku }}</td></tr>
                        <tr><th>Tanggal Pinjam</th><td>{{ \Carbon\Carbon::parse($transaksi->tgl_pinjam)->format('d M Y') }}</td></tr>
                        <tr><th>Tanggal Kembali</th><td>{{ $transaksi->tgl_kembali ? \Carbon\Carbon::parse($transaksi->tgl_kembali)->format('d M Y') : '-' }}</td></tr>
                        <tr><th>Status</th>
                            <td><span class="badge {{ $transaksi->status === 'dipinjam' ? 'bg-primary' : 'bg-success' }}">{{ ucfirst($transaksi->status) }}</span></td></tr>
                        <tr><th>Tanggal Jatuh Tempo</th><td>{{ $transaksi->tgl_jatuh_tempo?->format('d M Y') ?? '-' }}</td></tr>
                        <tr><th>Hari Terlambat</th>
                            <td>
                                {{ $transaksi->hari_terlambat }} hari
                                @if($transaksi->is_terlambat)
                                    <span class="badge-soft red ms-1">Terlambat</span>
                                @endif
                            </td>
                        </tr>
                        <tr><th>Denda</th>
                            <td>
                                @if($transaksi->isAktif())
                                    @if($transaksi->estimasi_denda > 0)
                                        <span class="fw-bold text-danger">Estimasi: Rp{{ number_format($transaksi->estimasi_denda, 0, ',', '.') }}</span>
                                        <small class="text-muted d-block">{{ $transaksi->hari_terlambat }} hari belum dibayar</small>
                                    @else
                                        Rp0
                                    @endif
                                @else
                                    @if($transaksi->denda > 0)
                                        Rp{{ number_format($transaksi->denda, 0, ',', '.') }}
                                    @else
                                        Rp0
                                    @endif
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.transaksi.edit', $transaksi) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
                    <form method="POST" action="{{ route('admin.transaksi.destroy', $transaksi) }}"
                        onsubmit="return confirm('Yakin hapus transaksi ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Hapus</button>
                    </form>
                    <a href="{{ route('admin.transaksi.index') }}" class="btn btn-outline-secondary ms-auto">Kembali</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
