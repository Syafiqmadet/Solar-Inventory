@extends('layouts.app')
@section('title', 'Add Item')
@section('content')
<div class="page-header">
    <h1><i class="bi bi-plus-circle me-2"></i>Add New Item</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Add a new solar project component to inventory</p>
</div>
<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="bi bi-box me-2"></i>Item Details</h6></div>
                <div class="card-body p-4">
                    <form action="{{ route('items.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Part Number <span class="text-danger">*</span></label>
                                <input type="text" name="part_number" class="form-control @error('part_number') is-invalid @enderror" value="{{ old('part_number') }}" placeholder="e.g. SLR-PNL-001" required>
                                @error('part_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. 400W Solar Panel" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Detailed description of the item...">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">Select Category</option>
                                    <option value="Panel" {{ old('category')=='Panel'?'selected':'' }}>Panel</option>
                                    <option value="Inverter" {{ old('category')=='Inverter'?'selected':'' }}>Inverter</option>
                                    <option value="Battery" {{ old('category')=='Battery'?'selected':'' }}>Battery</option>
                                    <option value="Mounting" {{ old('category')=='Mounting'?'selected':'' }}>Mounting</option>
                                    <option value="Wiring" {{ old('category')=='Wiring'?'selected':'' }}>Wiring</option>
                                    <option value="Other" {{ old('category')=='Other'?'selected':'' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Unit</label>
                                <select name="unit" class="form-select">
                                    <option value="pcs">pcs</option>
                                    <option value="meters">meters</option>
                                    <option value="kg">kg</option>
                                    <option value="sets">sets</option>
                                    <option value="rolls">rolls</option>
                                    <option value="boxes">boxes</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                @include('partials.color-swatch', ['fieldName'=>'color_code','fieldId'=>'color_code','default'=>old('color_code','#3B82F6'),'label'=>'Color Code'])
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Initial Stock</label>
                                <input type="number" name="current_stock" class="form-control" value="{{ old('current_stock', 0) }}" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Minimum Stock Alert</label>
                                <input type="number" name="min_stock" class="form-control" value="{{ old('min_stock', 5) }}" min="0">
                            </div>
                            <div class="col-12 mt-3 d-flex gap-2 justify-content-end">
                                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-solar px-4"><i class="bi bi-save me-2"></i>Save Item</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
