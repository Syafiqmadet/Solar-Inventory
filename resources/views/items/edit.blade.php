@extends('layouts.app')
@section('title', 'Edit Item')
@section('content')
<div class="page-header">
    <h1><i class="bi bi-pencil me-2"></i>Edit Item</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Update item: {{ $item->name }}</p>
</div>
<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white py-3"><h6 class="mb-0">Edit: {{ $item->name }}</h6></div>
                <div class="card-body p-4">
                    <form action="{{ route('items.update', $item) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Part Number *</label>
                                <input type="text" name="part_number" class="form-control" value="{{ old('part_number', $item->part_number) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Item Name *</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $item->name) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $item->description) }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Category</label>
                                <select name="category" class="form-select">
                                    @foreach(['Panel','Inverter','Battery','Mounting','Wiring','Other'] as $cat)
                                    <option value="{{ $cat }}" {{ old('category', $item->category)==$cat?'selected':'' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Unit</label>
                                <select name="unit" class="form-select">
                                    @foreach(['pcs','meters','kg','sets','rolls','boxes'] as $u)
                                    <option value="{{ $u }}" {{ old('unit', $item->unit)==$u?'selected':'' }}>{{ $u }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                @include('partials.color-swatch', ['fieldName'=>'color_code','fieldId'=>'color_code','default'=>old('color_code',$item->color_code ?? '#3B82F6'),'label'=>'Color Code'])
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Current Stock</label>
                                <input type="number" name="current_stock" class="form-control" value="{{ old('current_stock', $item->current_stock) }}" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Minimum Stock Alert</label>
                                <input type="number" name="min_stock" class="form-control" value="{{ old('min_stock', $item->min_stock) }}" min="0">
                            </div>
                            <div class="col-12 mt-3 d-flex gap-2 justify-content-end">
                                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-solar px-4"><i class="bi bi-save me-2"></i>Update Item</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
@stack('scripts')
@endsection
