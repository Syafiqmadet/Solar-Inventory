@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'Items')
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-box-seam me-2"></i>Items / Parts</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Manage all solar project components</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('items.export', request()->query()) }}" class="btn btn-success fw-semibold me-2">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            </a>
            @if($canWrite)
            <a href="{{ route('items.create') }}" class="btn btn-success fw-semibold me-2">
                <i class="bi bi-plus-circle me-2"></i>Add Item
            </a>
            @endif
        </div>
    </div>
</div>

<div class="content-area">

    {{-- Search & Filter --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('items.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">🔍 Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Name, part number, description…" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach(['Panel','Inverter','Battery','Mounting','Wiring','Other'] as $cat)
                        <option value="{{ $cat }}" {{ request('category')===$cat ? 'selected':'' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Stock Status</label>
                    <select name="stock" class="form-select form-select-sm">
                        <option value="">All Stock</option>
                        <option value="ok"  {{ request('stock')==='ok'  ? 'selected':'' }}>✅ OK</option>
                        <option value="low" {{ request('stock')==='low' ? 'selected':'' }}>⚠️ Low</option>
                        <option value="out" {{ request('stock')==='out' ? 'selected':'' }}>❌ Out</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-solar btn-sm w-100">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('items.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>All Items</h6>
            <span class="badge bg-secondary">{{ $items->total() }} items</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="px-4" style="width:40px">Color</th>
                            <th>Part Number</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Min</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th class="px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td class="px-4">
                                <span class="color-dot" style="background-color:{{ $item->color_code ?? '#cccccc' }}"></span>
                            </td>
                            <td><code>{{ $item->part_number }}</code></td>
                            <td class="fw-semibold">{{ $item->name }}</td>
                            <td class="text-muted small">{{ Str::limit($item->description, 50) }}</td>
                            <td><span class="badge bg-info text-dark">{{ $item->category ?? 'General' }}</span></td>
                            <td class="text-center fw-bold {{ $item->current_stock <= 0 ? 'text-danger' : ($item->current_stock <= $item->min_stock ? 'text-warning' : 'text-success') }}">
                                {{ $item->current_stock }}
                            </td>
                            <td class="text-center text-muted">{{ $item->min_stock }}</td>
                            <td class="text-muted small">{{ $item->unit ?? 'pcs' }}</td>
                            <td>
                                @if($item->current_stock <= 0)
                                    <span class="badge bg-danger">❌ Out</span>
                                @elseif($item->current_stock <= $item->min_stock)
                                    <span class="badge bg-warning text-dark">⚠️ Low</span>
                                @else
                                    <span class="badge bg-success">✅ OK</span>
                                @endif
                            </td>
                            <td class="px-4">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('items.show', $item) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                    @if($canWrite)
                                    <a href="{{ route('items.edit', $item) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('items.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this item?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:2rem"></i>
                        <p class="mt-2">No items found. @if($canWrite)<a href="{{ route('items.create') }}">Add your first item</a>@endif</p>
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($items->hasPages())
        <div class="card-footer bg-white">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@endsection
