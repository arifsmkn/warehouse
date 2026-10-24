<?php

namespace App\Models\Concerns;

/**
 * Dipakai buat "hide" baris data dummy yang tidak mau ditampilkan di
 * presentasi (mis. permintaan kecil tambahan selain 1 permintaan bulanan
 * utama per departemen) TANPA menghapusnya dari database -- reversible,
 * tinggal set hidden_at = null lagi kalau mau dimunculkan lagi.
 * Pakai withHidden() buat sementara lihat baris yang ter-hide juga.
 */
trait HasHiddenScope
{
    protected static function bootHasHiddenScope(): void
    {
        static::addGlobalScope('notHidden', function ($query) {
            $query->whereNull('hidden_at');
        });
    }

    public function scopeWithHidden($query)
    {
        return $query->withoutGlobalScope('notHidden');
    }
}
