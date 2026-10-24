@extends('layouts.app')
@section('title','Budget Kebutuhan GA')
@section('page-title','Budget per Item / Departemen — ' . now()->translatedFormat('F Y'))
@section('breadcrumb')
    <li class="breadcrumb-item">General Affair</li>
    <li class="breadcrumb-item active">Budget</li>
@endsection

@section('page-actions')
<div class="d-flex gap-2">
    <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importBudgetModal">
        <i class="bi bi-file-earmark-excel me-1"></i>Import Excel
    </button>
    <a href="{{ route('ga.budget.template') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Template
    </a>
</div>
@endsection

@section('content')

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Set Budget Default Baru</div>
    <div class="card-body">
        <form action="{{ route('ga.budget.store') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:12px;">Departemen</label>
                <select name="department_id" class="form-select form-select-sm select2-dept" required>
                    <option value="">-- Pilih --</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:12px;">Item</label>
                <select name="procurement_item_id" class="form-select form-select-sm select2-item" required>
                    <option value="">-- Pilih --</option>
                    @foreach($items as $i)
                    <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->item_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Budget Default / Bulan</label>
                <input type="number" step="0.01" min="0" name="default_budget" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-save"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-cash-coin me-2 text-primary"></i>Budget vs Actual Bulan Ini</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Departemen</th><th>Kode Item</th><th>Nama Item</th><th>Default</th><th>Top-up Bulan Ini</th>
                        <th>Effective Budget</th><th>Actual</th><th>Sisa</th><th width="200"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $b)
                    <tr class="{{ $b->remaining < 0 ? 'table-danger' : '' }}">
                        <td>{{ $b->department->name }}</td>
                        <td class="fw-semibold">{{ $b->procurementItem->item_code }}</td>
                        <td>{{ $b->procurementItem->name }}</td>
                        <td>Rp {{ number_format($b->default_budget, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($b->effective - $b->default_budget, 0, ',', '.') }}</td>
                        <td class="fw-semibold">Rp {{ number_format($b->effective, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($b->consumed, 0, ',', '.') }}</td>
                        <td class="fw-bold {{ $b->remaining < 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($b->remaining, 0, ',', '.') }}
                            @if($b->remaining < 0)<span class="badge bg-danger ms-1">Over Budget</span>@endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#topup-{{ $b->id }}">
                                <i class="bi bi-plus-lg"></i> Top-up
                            </button>
                        </td>
                    </tr>
                    <tr class="collapse" id="topup-{{ $b->id }}">
                        <td colspan="9" class="bg-light">
                            <form action="{{ route('ga.budget.topup', $b->id) }}" method="POST" class="row g-2 align-items-end py-2">
                                @csrf
                                <div class="col-md-3">
                                    <label class="form-label" style="font-size:11px;">Jumlah Top-up</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" style="font-size:11px;">Catatan</label>
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Alasan penambahan budget...">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success btn-sm w-100">Simpan</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada budget yang diset</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Import Budget</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('ga.budget.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-2 py-2 mb-3">
                        <i class="bi bi-info-circle"></i>
                        <div style="font-size:13px;">
                            File Excel/CSV harus memiliki kolom:
                            <strong>departemen, kode_item, budget_default</strong>
                            (kolom <strong>nama_item</strong> di template cuma buat referensi, tidak wajib & tidak dibaca sistem)
                            <br><a href="{{ route('ga.budget.template') }}" class="text-info">Download template</a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('.select2-dept, .select2-item').select2({ theme: 'bootstrap-5' });
</script>
@endpush
