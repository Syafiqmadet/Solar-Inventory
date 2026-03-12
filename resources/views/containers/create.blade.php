@extends('layouts.app')
@section('title', 'Add Container')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-plus-square me-2"></i>Add New Container</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Registering a container will <strong>increase item stock</strong> for each item added</p>
</div>

<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-10">

            {{-- Stock logic info --}}
            <div class="alert border-0 mb-4 p-3" style="background:#f0fdf4;border-left:4px solid #28a745 !important;border-left-style:solid !important">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-info-circle-fill text-success fs-5 mt-1"></i>
                    <div>
                        <strong class="text-success">Stock Logic</strong><br>
                        <span class="text-muted small">
                            When a container arrives (<strong>Date In</strong>), its items are added to warehouse stock — 
                            item <code>current_stock</code> <strong class="text-success">increases ↑</strong> by the item quantity.<br>
                            When a container is deleted, stock is automatically reversed.
                        </span>
                    </div>
                </div>
            </div>

            <form action="{{ route('containers.store') }}" method="POST" id="containerForm">
                @csrf

                {{-- Container Info --}}
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Container Info</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Container ID *</label>
                                <input type="text" name="container_id"
                                       class="form-control @error('container_id') is-invalid @enderror"
                                       value="{{ old('container_id') }}" placeholder="e.g. CNT-2024-001" required>
                                @error('container_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Batch</label>
                                <input list="batch-list" name="batch" class="form-control"
                                       value="{{ old('batch') }}" placeholder="e.g. BATCH-001">
                                <datalist id="batch-list">
                                    @foreach($batches as $b)<option value="{{ $b }}">@endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date In *</label>
                                <input type="date" name="date_in" class="form-control"
                                       value="{{ old('date_in') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date Out</label>
                                <input type="date" name="date_out" class="form-control"
                                       value="{{ old('date_out') }}">
                                <small class="text-muted">Leave empty if not yet out</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active"  {{ old('status','active')==='active'  ?'selected':'' }}>Active</option>
                                    <option value="pending" {{ old('status')==='pending'?'selected':'' }}>Pending</option>
                                    <option value="closed"  {{ old('status')==='closed' ?'selected':'' }}>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                @include('partials.color-swatch', ['fieldName'=>'color_code','fieldId'=>'container_color','default'=>old('color_code','#3B82F6'),'label'=>'Color Code'])
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="2"
                                          placeholder="Container contents overview…">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items in Container --}}
                <div class="card mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-boxes me-2"></i>Items in this Container
                            <span class="badge bg-success bg-opacity-10 text-success ms-2 fw-normal small">
                                <i class="bi bi-arrow-up-circle me-1"></i>Will increase item stock on save
                            </span>
                        </h6>
                        <button type="button" class="btn btn-solar btn-sm" id="addItemRow">
                            <i class="bi bi-plus me-1"></i>Add Item
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0 align-middle" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3" style="width:30%">Item / Part</th>
                                    <th style="width:15%">Part Number</th>
                                    <th>Description</th>
                                    <th style="width:100px">Qty</th>
                                    <th style="width:130px">Current Stock</th>
                                    <th style="width:130px">Stock After</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr class="item-row">
                                    <td class="px-3">
                                        <select name="items[0][item_id]" class="form-select form-select-sm item-select">
                                            <option value="">-- Select Item --</option>
                                            @foreach($items as $item)
                                            <option value="{{ $item->id }}"
                                                    data-pn="{{ $item->part_number }}"
                                                    data-desc="{{ $item->description }}"
                                                    data-stock="{{ $item->current_stock }}"
                                                    data-unit="{{ $item->unit }}">
                                                {{ $item->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][part_number]" class="form-control form-control-sm part-number-field" placeholder="Auto" readonly></td>
                                    <td><input type="text" name="items[0][description]" class="form-control form-control-sm desc-field" placeholder="Optional"></td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm qty-field" value="1" min="1"></td>
                                    <td><span class="stock-now badge bg-secondary">—</span></td>
                                    <td><span class="stock-after badge bg-success">—</span></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Stock summary footer --}}
                    <div class="card-footer bg-light py-2 d-none" id="stockSummary">
                        <small class="text-success fw-semibold"><i class="bi bi-graph-up-arrow me-1"></i>
                            Stock will increase for: <span id="stockSummaryText"></span>
                        </small>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('containers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-solar px-4">
                        <i class="bi bi-save me-2"></i>Save Container & Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@stack('scripts')
<script>
let rowIndex = 1;

// Build item options HTML once
@php
$itemsJson = $items->map(function($i) {
    return ['id' => $i->id, 'name' => $i->name, 'pn' => $i->part_number,
            'desc' => $i->description, 'stock' => $i->current_stock, 'unit' => $i->unit];
})->values()->toJson();
@endphp
const itemsData = {!! $itemsJson !!};

function buildOptions() {
    return itemsData.map(i =>
        `<option value="${i.id}" data-pn="${i.pn??''}" data-desc="${i.desc??''}" data-stock="${i.stock}" data-unit="${i.unit??'pcs'}">${i.name}</option>`
    ).join('');
}

function updateRowPreview(row) {
    const sel   = row.querySelector('.item-select');
    const qty   = parseInt(row.querySelector('.qty-field').value) || 0;
    const opt   = sel.selectedOptions[0];
    const nowEl = row.querySelector('.stock-now');
    const aftEl = row.querySelector('.stock-after');

    if (!opt || !opt.value) {
        nowEl.textContent = '—'; nowEl.className = 'stock-now badge bg-secondary';
        aftEl.textContent = '—'; aftEl.className = 'stock-after badge bg-secondary';
        return;
    }

    const stock = parseInt(opt.dataset.stock) || 0;
    const unit  = opt.dataset.unit || 'pcs';
    const after = stock + qty;

    nowEl.textContent = `${stock} ${unit}`;
    nowEl.className   = 'stock-now badge ' + (stock <= 0 ? 'bg-danger' : stock < 5 ? 'bg-warning text-dark' : 'bg-secondary');

    aftEl.textContent = `${after} ${unit}`;
    aftEl.className   = 'stock-after badge bg-success';

    updateSummary();
}

function updateSummary() {
    const lines = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const sel = row.querySelector('.item-select');
        const qty = parseInt(row.querySelector('.qty-field').value) || 0;
        const opt = sel?.selectedOptions[0];
        if (opt && opt.value && qty > 0) {
            lines.push(`+${qty} × ${opt.text.trim()}`);
        }
    });
    const summary = document.getElementById('stockSummary');
    const summaryText = document.getElementById('stockSummaryText');
    if (lines.length) {
        summary.classList.remove('d-none');
        summaryText.textContent = lines.join('  |  ');
    } else {
        summary.classList.add('d-none');
    }
}

function bindRow(row) {
    const sel = row.querySelector('.item-select');
    const qty = row.querySelector('.qty-field');

    sel.addEventListener('change', function() {
        const opt = this.selectedOptions[0];
        row.querySelector('.part-number-field').value = opt?.dataset.pn  ?? '';
        row.querySelector('.desc-field').value        = opt?.dataset.desc ?? '';
        updateRowPreview(row);
    });
    qty.addEventListener('input', () => updateRowPreview(row));
    row.querySelector('.remove-row').addEventListener('click', () => {
        row.remove();
        updateSummary();
    });
    updateRowPreview(row);
}

document.getElementById('addItemRow').addEventListener('click', function() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td class="px-3">
            <select name="items[${rowIndex}][item_id]" class="form-select form-select-sm item-select">
                <option value="">-- Select Item --</option>${buildOptions()}
            </select>
        </td>
        <td><input type="text"   name="items[${rowIndex}][part_number]" class="form-control form-control-sm part-number-field" placeholder="Auto" readonly></td>
        <td><input type="text"   name="items[${rowIndex}][description]" class="form-control form-control-sm desc-field" placeholder="Optional"></td>
        <td><input type="number" name="items[${rowIndex}][quantity]"    class="form-control form-control-sm qty-field" value="1" min="1"></td>
        <td><span class="stock-now  badge bg-secondary">—</span></td>
        <td><span class="stock-after badge bg-secondary">—</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>`;
    tbody.appendChild(tr);
    bindRow(tr);
    rowIndex++;
});

// Bind existing rows
document.querySelectorAll('.item-row').forEach(bindRow);
</script>
@endsection
