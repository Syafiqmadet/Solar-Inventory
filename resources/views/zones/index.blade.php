@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'Zones')
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-geo-alt me-2"></i>Zones</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Manage project zones and track stock movements</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('zones.export.all') }}" class="btn btn-success fw-semibold me-2">
                <i class="bi bi-file-earmark-excel me-2"></i>Export All
            </a>
            @if($canWrite)
            <a href="{{ route('zones.create') }}" class="btn btn-success fw-semibold me-2"><i class="bi bi-plus-circle me-2"></i>Add Zone</a>
            @endif
        </div>
    </div>
</div>
<div class="content-area">
    <div class="row g-4">
        @forelse($zones as $zone)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-header py-3" style="background:linear-gradient(135deg,{{ $zone->color ?? '#667eea' }},{{ $zone->color ?? '#764ba2' }})30;border-left:4px solid {{ $zone->color ?? '#667eea' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">{{ $zone->name }}</h6>
                        <span class="badge" style="background:{{ $zone->color ?? '#667eea' }}">{{ $zone->code ?? 'Z' }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">{{ $zone->description ?? 'No description' }}</p>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 rounded text-center" style="background:#e8f5e9">
                                <div class="fw-bold text-success">{{ $zone->stockIn ?? 0 }}</div>
                                <small class="text-muted">Stock In</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded text-center" style="background:#fce4ec">
                                <div class="fw-bold text-danger">{{ $zone->stockOut ?? 0 }}</div>
                                <small class="text-muted">Stock Out</small>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('zones.show', $zone) }}" class="btn btn-sm btn-outline-info flex-fill"><i class="bi bi-eye me-1"></i>View</a>
                        @if($canWrite)
                        <a href="{{ route('zones.stock.form', $zone) }}" class="btn btn-sm btn-solar flex-fill"><i class="bi bi-arrow-left-right me-1"></i>Stock</a>
                        <a href="{{ route('zones.edit', $zone) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('zones.destroy', $zone) }}" method="POST" onsubmit="return confirm('Delete this zone?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card text-center py-5">
                <i class="bi bi-geo-alt" style="font-size:3rem;color:#ccc"></i>
                <p class="mt-3 text-muted">No zones yet. @if($canWrite)<a href="{{ route('zones.create') }}">Create your first zone</a>@endif</p>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
