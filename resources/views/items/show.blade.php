@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', $item->name)
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-box me-2"></i>{{ $item->name }}</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Part: {{ $item->part_number }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($canWrite)
            <a href="{{ route('items.edit', $item) }}" class="btn btn-light"><i class="bi bi-pencil me-2"></i>Edit</a>
            @endif
            <a href="{{ route('items.index') }}" class="btn btn-outline-light">Back</a>
        </div>
    </div>
</div>
<div class="content-area">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:80px;height:80px;background:{{ $item->color_code ?? '#FF6B35' }}">
                        <i class="bi bi-box text-white" style="font-size:2rem"></i>
                    </div>
                    <h5>{{ $item->name }}</h5>
                    <code>{{ $item->part_number }}</code>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h3 class="{{ $item->current_stock <= ($item->min_stock ?? 5) ? 'text-danger' : 'text-success' }}">{{ $item->current_stock }}</h3>
                            <small class="text-muted">Current Stock</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-muted">{{ $item->min_stock ?? 5 }}</h3>
                            <small class="text-muted">Min Stock</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white py-3"><h6 class="mb-0">Item Information</h6></div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr><th width="150">Part Number</th><td><code>{{ $item->part_number }}</code></td></tr>
                        <tr><th>Name</th><td>{{ $item->name }}</td></tr>
                        <tr><th>Description</th><td>{{ $item->description ?? '-' }}</td></tr>
                        <tr><th>Category</th><td><span class="badge bg-info text-dark">{{ $item->category ?? 'General' }}</span></td></tr>
                        <tr><th>Unit</th><td>{{ $item->unit ?? 'pcs' }}</td></tr>
                        <tr><th>Color Code</th><td>
                            <span class="color-dot" style="background:{{ $item->color_code ?? '#ccc' }}"></span>
                            {{ $item->color_code ?? 'N/A' }}
                        </td></tr>
                    </table>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Transaction History</h6></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th class="px-3">Date</th><th>Type</th><th>Zone</th><th class="text-end px-3">Qty</th><th>Notes</th></tr></thead>
                        <tbody>
                            @forelse($item->transactions ?? [] as $tx)
                            <tr>
                                <td class="px-3 small text-muted">{{ $tx->created_at->format('d M Y H:i') }}</td>
                                <td><span class="badge {{ $tx->type=='in' ? 'bg-success' : 'bg-danger' }}">{{ strtoupper($tx->type) }}</span></td>
                                <td>{{ $tx->zone->name ?? '-' }}</td>
                                <td class="text-end px-3 fw-bold">{{ $tx->quantity }}</td>
                                <td class="small text-muted">{{ $tx->notes ?? '' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No transactions yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
