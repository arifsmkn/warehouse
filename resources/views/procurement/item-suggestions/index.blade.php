@extends('layouts.app')
@section('title', 'Usulan Item Baru')
@section('page-title', 'Usulan Item Baru Saya')
@section('breadcrumb')
    <li class="breadcrumb-item active">Usulan Item Baru</li>
@endsection
@section('page-actions')
    <a href="{{ route('procurement.item-suggestions.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Usulkan Item Baru
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Foto</th><th>Nama Item</th><th>UOM</th><th>Tanggal</th><th>Status</th><th>Keterangan Review</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suggestions as $s)
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
                        <td>{{ $s->created_at->format('d/m/Y') }}</td>
                        <td>
                            @if($s->status === 'pending')
                                <span class="badge bg-warning text-dark">Menunggu Review</span>
                            @elseif($s->status === 'approved')
                                <span class="badge bg-success">Disetujui{{ $s->procurementItem ? ' — '.$s->procurementItem->item_code : '' }}</span>
                            @else
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                        </td>
                        <td style="font-size:12px;">{{ $s->status === 'rejected' ? $s->rejection_reason : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:32px;"></i>
                            <p class="mt-2 mb-3">Belum ada usulan item baru</p>
                            <a href="{{ route('procurement.item-suggestions.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Usulkan Item Baru
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
