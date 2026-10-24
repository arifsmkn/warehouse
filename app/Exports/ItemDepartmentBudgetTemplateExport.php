<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ItemDepartmentBudgetTemplateExport implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return Department::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn(Department $dept) => new DepartmentBudgetSheetExport($dept))
            ->all();
    }
}
