<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\ItemDepartmentBudget;
use App\Models\ProcurementItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ItemDepartmentBudgetImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public int $imported = 0;
    public int $skipped  = 0;

    public function model(array $row): ?ItemDepartmentBudget
    {
        if (empty($row['departemen']) || empty($row['kode_item']) || !is_numeric($row['budget_default'] ?? null)) {
            $this->skipped++;
            return null;
        }

        $department = Department::whereRaw('LOWER(name) = ?', [strtolower(trim($row['departemen']))])->first();
        $item = ProcurementItem::where('item_code', strtoupper(trim($row['kode_item'])))->first();

        if (!$department || !$item) {
            $this->skipped++;
            return null;
        }

        $this->imported++;

        return ItemDepartmentBudget::updateOrCreate(
            ['department_id' => $department->id, 'procurement_item_id' => $item->id],
            ['default_budget' => $row['budget_default']]
        );
    }
}
