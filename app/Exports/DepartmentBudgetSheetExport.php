<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\ItemDepartmentBudget;
use App\Models\ProcurementItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DepartmentBudgetSheetExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private Department $department) {}

    public function collection(): Collection
    {
        // budget_default yang sudah ada dipakai buat prefill (biar sheet ini juga bisa
        // dipakai buat review/edit, bukan cuma isi baru) -- item tanpa budget dibiarkan
        // kosong, karena kosong berarti "skip" di ItemDepartmentBudgetImport (bukan 0).
        $existing = ItemDepartmentBudget::where('department_id', $this->department->id)
            ->pluck('default_budget', 'procurement_item_id');

        return ProcurementItem::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn(ProcurementItem $item) => [
                $this->department->name,
                $item->item_code,
                $item->name,
                $existing->get($item->id),
            ]);
    }

    public function headings(): array
    {
        return ['departemen', 'kode_item', 'nama_item', 'budget_default'];
    }

    public function title(): string
    {
        // Sheet title Excel max 31 char & tidak boleh \ / ? * [ ] :
        return substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $this->department->name), 0, 31);
    }
}
