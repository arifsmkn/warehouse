@extends('layouts.app')
@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')
@section('breadcrumb')
    <li class="breadcrumb-item active">Profil Saya</li>
@endsection

@section('content')
<div class="row justify-content-center g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person-circle me-2 text-primary"></i>Data Diri
            </div>
            <div class="card-body">
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 align-items-start">
                        <div class="col-md-3 text-center">
                            <img src="{{ $user->photo ? asset('storage/'.$user->photo) : asset('assets/images/avatar.png') }}"
                                 class="rounded-circle mb-2" style="width:110px;height:110px;object-fit:cover;" alt="Foto">
                            <input type="file" name="photo" class="form-control form-control-sm @error('photo') is-invalid @enderror" accept="image/*">
                            @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">NPK</label>
                                    <input type="text" class="form-control" value="{{ $user->npk }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Role</label>
                                    <input type="text" class="form-control" value="{{ $user->getRoleLabel() }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Departemen</label>
                                    <input type="text" class="form-control" value="{{ $user->department->name ?? '-' }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Telepon</label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $user->name) }}">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $user->email) }}">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-shield-lock me-2 text-primary"></i>Ganti Password
            </div>
            <div class="card-body">
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Password Saat Ini <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Ulangi Password Baru <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key me-1"></i>Ubah Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
