@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'User Management')
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-people me-2"></i>User Management</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Manage system access — admins and users</p>
        </div>
        @if($canWrite)
        <a href="{{ route('users.create') }}" class="btn btn-light fw-semibold">
            <i class="bi bi-person-plus me-2"></i>Add User
        </a>
        @endif
    </div>
</div>

<div class="content-area">
    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="mb-0 fw-bold">{{ $users->count() }}</h4>
                <small class="text-muted">Total Users</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="mb-0 fw-bold text-danger">{{ $users->where('role','admin')->count() }}</h4>
                <small class="text-muted">Admins</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="mb-0 fw-bold text-primary">{{ $users->where('role','user')->count() }}</h4>
                <small class="text-muted">Users</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="mb-0 fw-bold text-success">{{ $users->count() }}</h4>
                <small class="text-muted">Active Accounts</small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>All Accounts</h6>
            <span class="badge bg-secondary">{{ $users->count() }} accounts</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="px-4" style="width:40px">#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th class="px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $i => $user)
                    <tr class="{{ $user->id === auth()->id() ? 'table-warning' : '' }}">
                        <td class="px-4 text-muted">{{ $i + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                     style="width:36px;height:36px;flex-shrink:0;font-size:0.85rem;
                                     background:{{ $user->isAdmin() ? '#dc3545' : '#0d6efd' }}">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    @if($user->id === auth()->id())
                                    <small class="text-warning fw-semibold">← You</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">{{ $user->email }}</td>
                        <td>
                            @if($user->isAdmin())
                                <span class="badge bg-danger"><i class="bi bi-shield-fill me-1"></i>Admin</span>
                            @else
                                <span class="badge bg-primary"><i class="bi bi-person-fill me-1"></i>User</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="px-4">
                            <div class="d-flex gap-1">
                                @if($canWrite)
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if($user->id !== auth()->id() && $canWrite)
                                <form action="{{ route('users.destroy', $user) }}" method="POST"
                                      onsubmit="return confirm('Delete user {{ $user->name }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
