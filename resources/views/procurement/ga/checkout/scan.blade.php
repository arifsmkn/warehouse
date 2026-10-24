@extends('layouts.app')
@section('title','Scan Karyawan')
@section('page-title', 'Serah Terima — ' . $department->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ga.checkout.index') }}" class="text-decoration-none">Serah Terima Barang</a></li>
    <li class="breadcrumb-item active">{{ $department->name }}</li>
@endsection

@push('styles')
<style>
    #reader { border-radius: 12px; overflow: hidden; max-width: 320px; margin: 0 auto; }
    .scan-divider { position: relative; text-align: center; margin: 20px 0; }
    .scan-divider::before {
        content: '';
        position: absolute;
        top: 50%; left: 0; right: 0;
        height: 1px;
        background: #e9ecef;
    }
    .scan-divider span {
        position: relative;
        background: #fff;
        padding: 0 12px;
        color: #6c757d;
        font-size: 13px;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-body">
                <p class="text-muted text-center mb-3">Scan QR karyawan atau masukkan email untuk departemen <strong>{{ $department->name }}</strong></p>

                <div id="reader" class="mb-3"></div>

                <div class="scan-divider"><span>atau input manual</span></div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Email Karyawan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                        <input type="text" id="manualInput" class="form-control border-start-0" placeholder="email@sankei-dharma.com">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" id="manualPassword" class="form-control border-start-0 border-end-0" placeholder="Password akun karyawan">
                        <button type="button" class="input-group-text bg-light border-start-0" onclick="toggleManualPassword()">
                            <i class="bi bi-eye" id="manualEyeIcon"></i>
                        </button>
                    </div>
                    <div class="text-muted mt-1" style="font-size:12px;">Password cuma wajib untuk input manual — scan QR tidak perlu.</div>
                </div>

                <button class="btn btn-primary w-100" onclick="lookupManual()">
                    <i class="bi bi-search me-1"></i> Cari
                </button>

                <div id="lookupError" class="alert alert-danger py-2 mt-3 mb-0" style="display:none;font-size:13px;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const departmentId = {{ $department->id }};
let html5QrCode;

function initScanner() {
    html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        (decodedText) => { html5QrCode.stop(); lookupEmployee(decodedText, 'qr'); },
        (err) => {}
    ).catch(err => {});
}

function lookupEmployee(identifier, source, password) {
    if (!identifier || !identifier.trim()) return;

    const el = document.getElementById('lookupError');

    if (source === 'manual' && !password) {
        el.textContent = 'Password wajib diisi untuk input manual.';
        el.style.display = 'block';
        return;
    }

    fetch('{{ route("ga.checkout.lookup") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ department_id: departmentId, identifier: identifier.trim(), source, password })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            el.textContent = data.message;
            el.style.display = 'block';
            if (source === 'qr' && html5QrCode) initScanner();
        }
    });
}

function toggleManualPassword() {
    const input = document.getElementById('manualPassword');
    const icon = document.getElementById('manualEyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function lookupManual() {
    lookupEmployee(
        document.getElementById('manualInput').value,
        'manual',
        document.getElementById('manualPassword').value
    );
}

document.addEventListener('DOMContentLoaded', () => { initScanner(); });
document.getElementById('manualInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') lookupManual();
});
document.getElementById('manualPassword').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') lookupManual();
});
</script>
@endpush
