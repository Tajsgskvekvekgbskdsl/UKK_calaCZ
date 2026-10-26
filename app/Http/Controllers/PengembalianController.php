<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengembalian oleh USER (hanya transaksi miliknya sendiri).
 *
 * Alur: Pengembalian -> pilih transaksi aktif -> KEMBALIKAN
 *       -> status jadi dikembalikan + tgl_kembali terisi
 *       -> stok buku +1. Tidak bisa dikembalikan dua kali.
 */
class PengembalianController extends Controller
{
    /**
     * Daftar transaksi aktif milik user yang sedang login.
     */
    public function index()
    {
        $anggota = $this->authUser()->anggota;

        // Paginator KOSONG (bukan collect()) — view memanggil firstItem()/links()
        // yang hanya ada pada Paginator, bukan Collection.
        $pengembalians = Transaksi::whereRaw('1 = 0')->paginate(10);

        if ($anggota) {
            $pengembalians = Transaksi::with('buku')
                ->where('id_anggota', $anggota->id_anggota)
                ->where('status', 'dipinjam')
                ->latest('tgl_pinjam')
                ->paginate(10);
        }

        return view('site.pengembalian', compact('pengembalians'));
    }

    /**
     * Proses pengembalian satu buku.
     */
    public function store(Request $request, Transaksi $transaksi)
    {
        $user = $this->authUser();

        // Hanya pemilik transaksi yang boleh mengembalikan.
        if (! $user->anggota || (int) $transaksi->id_anggota !== (int) $user->anggota->id_anggota) {
            abort(403, 'Anda tidak berhak mengembalikan transaksi ini.');
        }

        if ($transaksi->status !== 'dipinjam') {
            return redirect()->route('user.pengembalian.index')
                ->with('error', 'Buku ini sudah dikembalikan sebelumnya.');
        }

        $sudahKembali = false;

        DB::transaction(function () use ($transaksi, &$sudahKembali) {
            // Kunci baris agar double-submit/race tidak mengembalikan dua kali.
            $t = Transaksi::whereKey($transaksi->id_transaksi)->lockForUpdate()->first();

            if ($t->status !== 'dipinjam') {
                $sudahKembali = true;

                return;
            }

            // Hitung denda keterlambatan dari tanggal jatuh tempo sampai
            // tanggal pengembalian aktual. Tak terlambat / tepat waktu -> 0.
            // Denda dihitung hanya berdasarkan hari kalender penuh.
            $tglKembali = now()->toDateString();
            $denda = Transaksi::hitungDenda($t->tgl_pinjam, $t->tgl_jatuh_tempo, $tglKembali);

            $t->update([
                'status' => 'dikembalikan',
                'tgl_kembali' => $tglKembali,
                'denda' => $denda,
            ]);

            // Stok buku +1.
            Buku::whereKey($t->id_buku)->increment('stok', 1);
        });

        if ($sudahKembali) {
            return redirect()->route('user.pengembalian.index')
                ->with('error', 'Buku ini sudah dikembalikan sebelumnya.');
        }

        $pesan = 'Buku berhasil dikembalikan. Terima kasih!';
        if ($denda > 0) {
            $pesan .= ' Denda keterlambatan: Rp' . number_format($denda, 0, ',', '.');
        }

        return redirect()->route('user.pengembalian.index')->with('success', $pesan);
    }
}
