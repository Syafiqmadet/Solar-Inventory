@extends('layouts.app')
@section('title', 'Isolate Item')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-shield-exclamation me-2"></i>Isolate Item</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Record a defective or damaged item</p>
</div>

<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="alert border-0 mb-4 p-3 d-flex gap-3" style="background:#fff8f0">
                <div class="p-3 rounded text-center flex-fill" style="background:#fff3cd;border-left:4px solid #ffc107">
                    <div class="fw-bold" style="color:#664d03">⚠️ Defect</div>
                    <div class="small text-muted mt-1">Item has a manufacturing or functional fault but may be repairable</div>
                </div>
                <div class="p-3 rounded text-center flex-fill" style="background:#f8d7da;border-left:4px solid #dc3545">
                    <div class="fw-bold text-danger">💥 Damaged</div>
                    <div class="small text-muted mt-1">Item is physically broken, crushed, or otherwise beyond repair</div>
                </div>
            </div>

            <form action="{{ route('isolated.store') }}" method="POST">
                @csrf
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Item Details</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            {{-- Link to existing inventory item (optional) --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Link to Inventory Item
                                    <span class="text-muted fw-normal small">(optional — stock will be deducted)</span>
                                </label>
                                <select name="item_id" class="form-select" id="itemSelect">
                                    <option value="">— Not linked / manual entry —</option>
                                    @foreach($items as $item)
                                    <option value="{{ $item->id }}"
                                            data-name="{{ $item->name }}"
                                            data-pn="{{ $item->part_number }}"
                                            data-stock="{{ $item->current_stock }}"
                                            {{ old('item_id') == $item->id ? 'selected':'' }}>
                                        [{{ $item->part_number }}] {{ $item->name }}
                                        (Stock: {{ $item->current_stock }})
                                    </option>
                                    @endforeach
                                </select>
                                <div id="linkedStockInfo" class="small text-muted mt-1" style="display:none">
                                    Available stock: <strong id="availableStock"></strong>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Item Name *</label>
                                <input type="text" name="name" id="nameField"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="e.g. Solar Panel 450W" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Part Number</label>
                                <input type="text" name="part_number" id="partField"
                                       class="form-control"
                                       value="{{ old('part_number') }}" placeholder="e.g. SP-450W-001">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Quantity *</label>
                                <input type="number" name="quantity" id="qtyField"
                                       class="form-control @error('quantity') is-invalid @enderror"
                                       value="{{ old('quantity', 1) }}" min="1" required>
                                @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Isolated Date *</label>
                                <input type="date" name="isolated_date"
                                       class="form-control @error('isolated_date') is-invalid @enderror"
                                       value="{{ old('isolated_date', date('Y-m-d')) }}" required>
                                @error('isolated_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Status *</label>
                                <select name="status" class="form-select">
                                    <option value="isolated" {{ old('status','isolated')==='isolated' ? 'selected':'' }}>🔒 Isolated</option>
                                    <option value="scrapped" {{ old('status')==='scrapped' ? 'selected':'' }}>🗑️ Scrapped</option>
                                    <option value="repaired" {{ old('status')==='repaired' ? 'selected':'' }}>✅ Repaired</option>
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

                            {{-- Type toggle --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">Type *</label>
                                <div class="d-flex gap-3">
                                    <div class="flex-fill">
                                        <input type="radio" name="type" value="defect"
                                               class="btn-check" id="typeDefect"
                                               {{ old('type','defect')==='defect' ? 'checked':'' }}>
                                        <label class="btn btn-outline-warning w-100 py-3" for="typeDefect">
                                            <div class="fs-4">⚠️</div>
                                            <div class="fw-bold">Defect</div>
                                            <div class="small">Functional fault / malfunction</div>
                                        </label>
                                    </div>
                                    <div class="flex-fill">
                                        <input type="radio" name="type" value="damaged"
                                               class="btn-check" id="typeDamaged"
                                               {{ old('type')==='damaged' ? 'checked':'' }}>
                                        <label class="btn btn-outline-danger w-100 py-3" for="typeDamaged">
                                            <div class="fs-4">💥</div>
                                            <div class="fw-bold">Damaged</div>
                                            <div class="small">Physical damage / broken</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Reason / Description *</label>
                                <textarea name="reason" rows="3"
                                          class="form-control @error('reason') is-invalid @enderror"
                                          placeholder="Describe what is wrong with the item, how it was discovered, and any relevant context…">{{ old('reason') }}</textarea>
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                                <textarea name="notes" rows="2"
                                          class="form-control"
                                          placeholder="Additional notes, action taken, follow-up…">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('isolated.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-solar px-4">
                        <i class="bi bi-shield-exclamation me-2"></i>Isolate Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const itemSel = document.getElementById('itemSelect');
const nameField = document.getElementById('nameField');
const partField = document.getElementById('partField');
const stockInfo = document.getElementById('linkedStockInfo');
const availStock = document.getElementById('availableStock');

itemSel.addEventListener('change', function () {
    const opt = this.selectedOptions[0];
    if (!opt.value) {
        stockInfo.style.display = 'none';
        return;
    }
    // Auto-fill name and part number from selected item
    if (!nameField.value || nameField.dataset.autofilled) {
        nameField.value = opt.dataset.name || '';
        nameField.dataset.autofilled = '1';
    }
    if (!partField.value || partField.dataset.autofilled) {
        partField.value = opt.dataset.pn || '';
        partField.dataset.autofilled = '1';
    }
    availStock.textContent = opt.dataset.stock;
    stockInfo.style.display = '';
});

nameField.addEventListener('input', () => delete nameField.dataset.autofilled);
partField.addEventListener('input', () => delete partField.dataset.autofilled);
</script>
@endsection
