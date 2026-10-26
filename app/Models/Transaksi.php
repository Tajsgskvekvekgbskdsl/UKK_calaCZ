<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';

    protected $primaryKey = 'id_transaksi';

    protected $fillable = [
        'id_anggota',
        'id_buku',
        'tgl_pinjam',
        'tgl_jatuh_tempo',
        'tgl_kembali',
        'denda',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pinjam' => 'date',
            'tgl_jatuh_tempo' => 'date',
            'tgl_kembali' => 'date',
            'denda' => 'integer',
        ];
    }

    /**
     * Status transaksi yang masih meminjam buku.
     */
    public static function statusAktif(): array
    {
        return ['dipinjam'];
    }

    public function isAktif(): bool
    {
        return in_array($this->status, self::statusAktif(), true);
    }

    public static function hitungDenda(string|Carbon|null $tglPinjam, string|Carbon|null $jatuhTempo = null, string|Carbon|null $sampai = null): int
    {
        if (! $tglPinjam) {
            return 0;
        }

        $batas = $jatuhTempo
            ? Carbon::parse($jatuhTempo)->startOfDay()
            : Carbon::parse($tglPinjam)->addDays(config('denda.batas_peminjaman_hari', 7))->startOfDay();

        $akhir = $sampai ? Carbon::parse($sampai)->startOfDay() : now()->startOfDay();

        if ($akhir->lt($batas)) {
            return 0;
        }

        $hariTerlambat = (int) $batas->diffInDays($akhir);

        return max(0, $hariTerlambat) * (int) config('denda.biaya_per_hari', 1000);
    }

    /**
     * Hitung denda untuk instance transaksi ini (sampai waktu tertentu).
     */
    public function hitungDendaInstance(?Carbon $sampai = null): int
    {
        return self::hitungDenda($this->tgl_pinjam, $this->tgl_jatuh_tempo, $sampai ?? $this->tgl_kembali);
    }

    /**
     * Jumlah hari keterlambatan relatif terhadap tanggal jatuh tempo.
     * Untuk transaksi belum dikembalikan, diitung sampai "hari ini".
     */
    public function getHariTerlambatAttribute(): int
    {
        if (! $this->tgl_jatuh_tempo) {
            return 0;
        }

        $akhir = $this->tgl_kembali ?? now();
        $hari = (int) Carbon::parse($this->tgl_jatuh_tempo)
            ->startOfDay()
            ->diffInDays(Carbon::parse($akhir)->startOfDay());

        return max(0, $hari);
    }

 
    public function getEstimasiDendaAttribute(): int
    {
        if ($this->isAktif()) {
            return $this->hitungDendaInstance();
        }

        return max(0, (int) $this->denda);
    }

    /**
     * Apakah transaksi ini terlambat (masih dipinjam dan melewati jatuh tempo).
     */
    public function getIsTerlambatAttribute(): bool
    {
        if ($this->status === 'dikembalikan') {
            return false;
        }

        $batas = $this->tgl_jatuh_tempo
            ?? Carbon::parse($this->tgl_pinjam)->addDays(config('denda.batas_peminjaman_hari', 7));

        return now()->gt(Carbon::parse($batas));
    }

    /**
     * ERD: transaksi N -- 1 anggota.
     */
    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'id_anggota', 'id_anggota');
    }

    /**
     * ERD: transaksi N -- 1 buku.
     */
    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'id_buku', 'id_buku');
    }
}

