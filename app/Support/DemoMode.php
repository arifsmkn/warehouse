<?php

namespace App\Support;

/**
 * Helper terpusat buat baca rentang DEMO_MODE_FROM/TO (.env) -- dipakai oleh
 * HasDemoModeScope (buat query Eloquent biasa) DAN controller yang query
 * pakai join manual (DB::table/->join() ke nama tabel mentah), yang TIDAK
 * ikut ke-filter otomatis oleh global scope model karena tidak lewat
 * instance Eloquent model-nya.
 */
class DemoMode
{
    public static function active(): bool
    {
        return (bool) config('app.demo_mode.from') && (bool) config('app.demo_mode.to');
    }

    /** @return array{0:string,1:string}|null [from, to inclusive end-of-day] */
    public static function range(): ?array
    {
        if (!static::active()) {
            return null;
        }
        return [config('app.demo_mode.from'), config('app.demo_mode.to') . ' 23:59:59'];
    }
}
