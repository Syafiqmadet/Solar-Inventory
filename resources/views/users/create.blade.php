@extends('layouts.app')
@section('title', 'Add User')
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-person-plus me-2"></i>Add New User</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Create a new system account</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-light">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-person-fill me-2"></i>User Details</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Ahmad Zaidi" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="e.g. ahmad@solar.com" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="">— Select Role —</option>
                                <option value="viewer" {{ old('role')==='viewer' ? 'selected':'' }}>👁 Viewer (read-only + export)</option>
                                <option value="user"  {{ old('role')==='user'  ? 'selected':'' }}>
                                    👤 User — Can access all inventory features
                                </option>
                                <option value="admin" {{ old('role')==='admin' ? 'selected':'' }}>
                                    🛡️ Admin — Full access + manage users
                                </option>
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Minimum 6 characters" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control"
                                   placeholder="Repeat password" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-solar px-4">
                                <i class="bi bi-person-check me-2"></i>Create User
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
