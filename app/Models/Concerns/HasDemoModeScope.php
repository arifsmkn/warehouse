<?php

namespace App\Models\Concerns;

use App\Support\DemoMode;

/**
 * Dipakai model riwayat (ProcurementRequest/PurchaseRequest/Transaction) buat
 * otomatis membatasi SEMUA query ke rentang tanggal DEMO_MODE_FROM/TO (.env)
 * -- supaya aplikasi cuma menampilkan data dummy presentasi QCC tanpa
 * menghapus data asli. Model yang pakai trait ini wajib set properti
 * $demoModeDateColumn (nama kolom tanggal yang difilter).
 *
 * CATATAN: scope ini cuma aktif untuk query yang lewat Eloquent model ini
 * sendiri -- query join manual ke nama tabel mentah (DB::table/->join()) di
 * controller TIDAK ikut ter-filter, harus ditambal manual pakai DemoMode::range().
 */
trait HasDemoModeScope
{
    protected static function bootHasDemoModeScope(): void
    {
        $range = DemoMode::range();
        if (!$range) {
            return;
        }

        static::addGlobalScope('demoMode', function ($query) use ($range) {
            $query->whereBetween((new static)->demoModeDateColumn, $range);
        });
    }
}
