<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periksa', function (Blueprint $table) {
            // belum_bayar -> menunggu_verifikasi (pasien upload bukti) -> lunas (admin verifikasi)
            $table->string('status_bayar', 25)->default('belum_bayar')->after('biaya_periksa');
            $table->string('bukti_bayar')->nullable()->after('status_bayar');
        });
    }

    public function down(): void
    {
        Schema::table('periksa', function (Blueprint $table) {
            $table->dropColumn(['status_bayar', 'bukti_bayar']);
        });
    }
};