<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            // Tanggal maksimal meminjam (tanggal jatuh tempo). Diisi saat peminjaman
            // dan tidak berubah meski pengembalian molor.
            $table->date('tgl_jatuh_tempo')->nullable()->after('tgl_kembali');

            // Total denda keterlambatan yang harus dibayar (rupiah). Nilainya
            // dihitung otomatis pada momen pengembalian; 0 bila tepat waktu.
            $table->unsignedBigInteger('denda')->default(0)->after('tgl_jatuh_tempo');
        });

        // Isi tanggal jatuh tempo otomatis untuk transaksi yang belum memiliki nilai,
        // termasuk data lama. Denda tetap 0 — diitung saat ada aksi kembali.
        $batasHari = config('denda.batas_peminjaman_hari', 7);

        DB::table('transaksi')->whereNull('tgl_jatuh_tempo')->get()->each(function ($t) use ($batasHari) {
            if ($t->tgl_pinjam) {
                $jatuhTempo = Carbon::parse($t->tgl_pinjam)->addDays($batasHari)->toDateString();

                DB::table('transaksi')
                    ->where('id_transaksi', $t->id_transaksi)
                    ->update(['tgl_jatuh_tempo' => $jatuhTempo]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn(['tgl_jatuh_tempo', 'denda']);
        });
    }
};
