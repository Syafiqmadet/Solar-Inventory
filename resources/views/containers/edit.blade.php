@extends('layouts.app')
@section('title', 'Edit Container')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-pencil me-2"></i>Edit Container: {{ $container->container_id }}</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Editing only updates container info — item stock is not changed here</p>
</div>

<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-9">

            <div class="alert border-0 mb-4 p-3" style="background:#fff8e1">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1"></i>
                    <div>
                        <strong>Note on Stock</strong><br>
                        <span class="text-muted small">
                            Stock was already adjusted when this container was first created.
                            Editing container info here does <strong>not</strong> change item stock.
                            To adjust items, delete this container (which reverses stock) and re-create it.
                        </span>
                    </div>
                </div>
            </div>

            <form action="{{ route('containers.update', $container) }}" method="POST">
                @csrf @method('PUT')
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">Container Info</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Container ID *</label>
                                <input type="text" name="container_id"
                                       class="form-control @error('container_id') is-invalid @enderror"
                                       value="{{ old('container_id', $container->container_id) }}" required>
                                @error('container_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Batch</label>
                                <input list="batch-list" name="batch" class="form-control"
                                       value="{{ old('batch', $container->batch) }}" placeholder="e.g. BATCH-001">
                                <datalist id="batch-list">
                                    @foreach($batches as $b)<option value="{{ $b }}">@endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date In *</label>
                                <input type="date" name="date_in" class="form-control"
                                       value="{{ old('date_in', $container->date_in) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date Out</label>
                                <input type="date" name="date_out" class="form-control"
                                       value="{{ old('date_out', $container->date_out) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    @foreach(['active','pending','closed'] as $s)
                                    <option value="{{ $s }}" {{ old('status',$container->status)===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                @include('partials.color-swatch', ['fieldName'=>'color_code','fieldId'=>'container_color','default'=>old('color_code',$container->color_code ?? '#3B82F6'),'label'=>'Color'])
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="2">{{ old('description', $container->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Read-only items summary --}}
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-boxes me-2"></i>Items in this Container (read-only)</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3">Item Name</th>
                                    <th>Part Number</th>
                                    <th>Description</th>
                                    <th class="text-center">Qty Added to Stock</th>
                                    <th class="text-center">Current Item Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($container->items as $ci)
                                <tr>
                                    <td class="px-3 fw-semibold">{{ $ci->item->name ?? '—' }}</td>
                                    <td><code class="small">{{ $ci->part_number ?? $ci->item->part_number ?? '—' }}</code></td>
                                    <td class="text-muted small">{{ $ci->description ?? '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success">+{{ $ci->quantity }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($ci->item)
                                            <span class="badge {{ $ci->item->current_stock <= 0 ? 'bg-danger' : ($ci->item->current_stock < $ci->item->min_stock ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                {{ $ci->item->current_stock }} {{ $ci->item->unit }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No items in this container</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('containers.show', $container) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-solar px-4"><i class="bi bi-save me-2"></i>Update Container</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
@stack('scripts')
@endsection
