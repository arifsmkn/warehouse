@extends('layouts.app')
@section('title', 'Usulkan Item Baru')
@section('page-title', 'Usulkan Item Baru')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('procurement.item-suggestions.index') }}" class="text-decoration-none">Usulan Item Baru</a></li>
    <li class="breadcrumb-item active">Usulkan</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Usulkan Item Baru</div>
            <div class="card-body">
                <p class="text-muted" style="font-size:12px;">
                    Item belum ada di katalog ATK? Usulkan di sini — nanti direview GA, kalau disetujui
                    item akan dilengkapi harga &amp; kategorinya lalu bisa dipesan di permintaan selanjutnya.
                </p>

                <form action="{{ route('procurement.item-suggestions.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Item <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="Contoh: Map Plastik Warna Merah">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">UOM / Satuan <span class="text-danger">*</span></label>
                        <select name="uom_id" class="form-select @error('uom_id') is-invalid @enderror">
                            <option value="">-- Pilih Satuan --</option>
                            @foreach($uoms as $uom)
                            <option value="{{ $uom->id }}" {{ old('uom_id') == $uom->id ? 'selected' : '' }}>{{ $uom->code }} — {{ $uom->name }}</option>
                            @endforeach
                        </select>
                        @error('uom_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Foto (opsional)</label>
                        <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                        @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan (opsional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Spesifikasi, alasan kebutuhan, dll.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Kirim Usulan</button>
                        <a href="{{ route('procurement.item-suggestions.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
