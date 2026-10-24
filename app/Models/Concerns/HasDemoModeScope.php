<?php

namespace App\Models\Concerns;

/**
 * Dipakai model riwayat (ProcurementRequest/PurchaseRequest/Transaction) buat
 * otomatis membatasi SEMUA query ke rentang tanggal DEMO_MODE_FROM/TO (.env)
 * -- supaya aplikasi cuma menampilkan data dummy presentasi QCC tanpa
 * menghapus data asli. Model yang pakai trait ini wajib set properti
 * $demoModeDateColumn (nama kolom tanggal yang difilter).
 */
trait HasDemoModeScope
{
    protected static function bootHasDemoModeScope(): void
    {
        $from = config('app.demo_mode.from');
        $to   = config('app.demo_mode.to');

        if (!$from || !$to) {
            return;
        }

        // Tambahkan jam akhir hari ke batas "to" -- kolom datetime (mis.
        // created_at) kalau cuma dikasih "2026-09-30" akan dibaca sebagai
        // 00:00:00, jadi hampir seluruh tanggal 30 Sep ikut kebuang.
        $toInclusive = $to . ' 23:59:59';

        static::addGlobalScope('demoMode', function ($query) use ($from, $toInclusive) {
            $query->whereBetween((new static)->demoModeDateColumn, [$from, $toInclusive]);
        });
    }
}
