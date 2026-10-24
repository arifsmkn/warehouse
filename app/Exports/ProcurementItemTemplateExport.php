<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProcurementItemTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['ATK-001', 'Pulpen Ballpoint', 'PCS', 'Alat Tulis', 3500],
            ['ATK-002', 'Kertas HVS A4 70gr', 'RIM', 'Kertas', 45000],
        ];
    }

    public function headings(): array
    {
        return ['kode_item', 'nama_item', 'uom', 'kategori', 'harga'];
    }
}
