<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_request_details', function (Blueprint $table) {
            // Kumulatif qty yang SUDAH dikreditkan ke stok lokal untuk baris ini
            // -- diisi baik oleh alur submit biasa (store()) maupun oleh sync
            // manual dari QAD (syncFromQad()), supaya keduanya tidak pernah
            // mengkredit qty yang sama dua kali walau dipakai bergantian.
            $table->decimal('qty_received_confirmed', 12, 2)->default(0)->after('qty_needed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_details', function (Blueprint $table) {
            $table->dropColumn('qty_received_confirmed');
        });
    }
};
