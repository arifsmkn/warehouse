@extends('layouts.app')
@section('title', 'Review Usulan Item Baru')
@section('page-title', 'Review Usulan Item Baru')
@section('breadcrumb')
    <li class="breadcrumb-item active">Review Usulan Item Baru</li>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header fw-semibold"><i class="bi bi-hourglass-split me-2 text-warning"></i>Menunggu Review ({{ $pending->count() }})</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th><th>Nama Item</th><th>UOM</th><th>Diusulkan Oleh</th><th>Keterangan</th><th>Tanggal</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pending as $s)
                    <tr>
                        <td>
                            @if($s->photo)
                                <img src="{{ asset('storage/'.$s->photo) }}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="width:40px;height:40px;border-radius:6px;"><i class="bi bi-box-seam"></i></div>
                            @endif
                        </td>
                        <td class="fw-semibold">{{ $s->name }}</td>
                        <td>{{ $s->uom->code }}</td>
                        <td>{{ $s->user->name }}<div class="text-muted" style="font-size:11px;">{{ $s->user->department?->name ?? '-' }}</div></td>
                        <td style="font-size:12px;">{{ $s->notes ?: '-' }}</td>
                        <td>{{ $s->created_at->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-success"
                                    onclick="showApproveModal({{ $s->id }}, '{{ $s->name }}')">
                                <i class="bi bi-check-lg"></i> Setujui
                            </button>
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="showRejectModal({{ $s->id }}, '{{ $s->name }}')">
                                <i class="bi bi-x-lg"></i> Tolak
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:32px;"></i>
                            <p class="mt-2 mb-0">Tidak ada usulan yang menunggu review</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Review</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama Item</th><th>Diusulkan Oleh</th><th>Status</th><th>Direview Oleh</th><th>Tanggal Review</th><th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $s)
                    <tr>
                        <td class="fw-semibold">{{ $s->name }}</td>
                        <td>{{ $s->user->name }}</td>
                        <td>
                            @if($s->status === 'approved')
                                <span class="badge bg-success">Disetujui{{ $s->procurementItem ? ' — '.$s->procurementItem->item_code : '' }}</span>
                            @else
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                        </td>
                        <td>{{ $s->reviewer?->name ?? '-' }}</td>
                        <td>{{ $s->reviewed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td style="font-size:12px;">{{ $s->status === 'rejected' ? $s->rejection_reason : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat review</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Approve Modal --}}
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-success"><i class="bi bi-check-circle me-2"></i>Setujui Usulan Item</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <p class="text-muted mb-3" style="font-size:13px;">Usulan <strong id="approveItemName"></strong> akan dibuat jadi item ATK resmi. Lengkapi harga &amp; kategorinya.</p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px;">Prefix Kode Item <span class="text-danger">*</span></label>
                        <select name="prefix" class="form-select" required>
                            @forelse($prefixes as $p)
                            <option value="{{ $p->code }}">{{ $p->code }}</option>
                            @empty
                            <option value="">-- Belum ada --</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:12px;">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Tanpa Kategori --</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold" style="font-size:12px;">Harga per Satuan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="price" min="0" step="1" class="form-control" placeholder="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Setujui &amp; Buat Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Tolak Usulan Item</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <p class="text-muted mb-3" style="font-size:13px;">Usulan <strong id="rejectItemName"></strong> akan ditolak.</p>
                    <label class="form-label fw-semibold" style="font-size:12px;">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Usulan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showApproveModal(id, name) {
    document.getElementById('approveItemName').textContent = name;
    document.getElementById('approveForm').action = `{{ url('ga/item-suggestions') }}/${id}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
function showRejectModal(id, name) {
    document.getElementById('rejectItemName').textContent = name;
    document.getElementById('rejectForm').action = `{{ url('ga/item-suggestions') }}/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
