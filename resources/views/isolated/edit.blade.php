@extends('layouts.app')
@section('title', 'Edit Isolated Item')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-shield-exclamation me-2"></i>Edit Isolated Item</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Update record for: <strong>{{ $isolated->name }}</strong></p>
</div>

<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form action="{{ route('isolated.update', $isolated) }}" method="POST">
                @csrf @method('PUT')

                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Item Details</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Link to Inventory Item
                                    <span class="text-muted fw-normal small">(optional — stock will be adjusted)</span>
                                </label>
                                <select name="item_id" class="form-select" id="itemSelect">
                                    <option value="">— Not linked / manual entry —</option>
                                    @foreach($items as $item)
                                    <option value="{{ $item->id }}"
                                            data-name="{{ $item->name }}"
                                            data-pn="{{ $item->part_number }}"
                                            data-stock="{{ $item->current_stock }}"
                                            {{ old('item_id', $isolated->item_id) == $item->id ? 'selected':'' }}>
                                        [{{ $item->part_number }}] {{ $item->name }}
                                        (Stock: {{ $item->current_stock }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Item Name *</label>
                                <input type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $isolated->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Part Number</label>
                                <input type="text" name="part_number" class="form-control"
                                       value="{{ old('part_number', $isolated->part_number) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Quantity *</label>
                                <input type="number" name="quantity"
                                       class="form-control @error('quantity') is-invalid @enderror"
                                       value="{{ old('quantity', $isolated->quantity) }}" min="1" required>
                                @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Isolated Date *</label>
                                <input type="date" name="isolated_date"
                                       class="form-control @error('isolated_date') is-invalid @enderror"
                                       value="{{ old('isolated_date', $isolated->isolated_date->format('Y-m-d')) }}" required>
                                @error('isolated_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Status *</label>
                                <select name="status" class="form-select">
                                    @foreach(['isolated'=>'🔒 Isolated','scrapped'=>'🗑️ Scrapped','repaired'=>'✅ Repaired'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ old('status',$isolated->status)===$val ? 'selected':'' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-card-text me-2"></i>Type &amp; Reason</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold">Type *</label>
                                <div class="d-flex gap-3">
                                    <div class="flex-fill">
                                        <input type="radio" name="type" value="defect" class="btn-check" id="typeDefect"
                                               {{ old('type',$isolated->type)==='defect' ? 'checked':'' }}>
                                        <label class="btn btn-outline-warning w-100 py-3" for="typeDefect">
                                            <div class="fs-4">⚠️</div>
                                            <div class="fw-bold">Defect</div>
                                            <div class="small">Functional fault / malfunction</div>
                                        </label>
                                    </div>
                                    <div class="flex-fill">
                                        <input type="radio" name="type" value="damaged" class="btn-check" id="typeDamaged"
                                               {{ old('type',$isolated->type)==='damaged' ? 'checked':'' }}>
                                        <label class="btn btn-outline-danger w-100 py-3" for="typeDamaged">
                                            <div class="fs-4">💥</div>
                                            <div class="fw-bold">Damaged</div>
                                            <div class="small">Physical damage / broken</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Reason / Description</label>
                                <textarea name="reason" rows="3" class="form-control"
                                          placeholder="Describe what is wrong with the item…">{{ old('reason', $isolated->reason) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                                <textarea name="notes" rows="2" class="form-control"
                                          placeholder="Additional notes, action taken…">{{ old('notes', $isolated->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('isolated.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-solar px-4">
                        <i class="bi bi-save me-2"></i>Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
