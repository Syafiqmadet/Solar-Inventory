@extends('layouts.app')
@section('title', 'Stock Transaction')

@php
$itemsForJs = $items->map(function($i) {
    return [
        'id'    => $i->id,
        'name'  => $i->name,
        'pn'    => $i->part_number ?? '',
        'stock' => (int)$i->current_stock,
        'unit'  => $i->unit ?? 'pcs',
        'min'   => (int)$i->min_stock,
    ];
})->values()->toJson();
@endphp

@section('content')
<div class="page-header">
    <h1><i class="bi bi-arrow-left-right me-2"></i>Stock In / Out</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Zone: <strong>{{ $zone->name }}</strong></p>
</div>

<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-8">

            {{-- Info box --}}
            <div class="alert border-0 mb-4 p-3" style="background:#f0f7ff">
                <div class="row g-2 text-center">
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background:#e8f5e9;border-left:4px solid #28a745">
                            <div class="fw-bold text-success mb-1"><i class="bi bi-arrow-down-circle me-1"></i>Stock IN</div>
                            <div class="small text-muted">Items <strong>deployed</strong> to zone.<br>Warehouse stock <strong class="text-danger">decreases ↓</strong></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background:#fce4ec;border-left:4px solid #dc3545">
                            <div class="fw-bold text-danger mb-1"><i class="bi bi-arrow-up-circle me-1"></i>Stock OUT — Return</div>
                            <div class="small text-muted">Items <strong>returned</strong> to warehouse.<br>Warehouse stock <strong class="text-success">increases ↑</strong></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background:#fff3e0;border-left:4px solid #ff9800">
                            <div class="fw-bold mb-1" style="color:#e65100"><i class="bi bi-shield-exclamation me-1"></i>Stock OUT — Defect/Damaged</div>
                            <div class="small text-muted">Item <strong>isolated</strong>. Stock not returned.<br>Auto-added to <strong>Isolated Items</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Record Stock Movement</h6>
                </div>
                <div class="card-body p-4">

                    @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('zones.stock', $zone) }}" method="POST" id="stockForm">
                        @csrf
                        <div class="row g-3">

                            {{-- ── Searchable Item Picker ── --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Select Item <span class="text-danger">*</span>
                                </label>

                                {{-- Real hidden input submitted with form --}}
                                <input type="hidden" name="item_id" id="hiddenItemId" value="{{ old('item_id') }}">

                                {{-- Search input --}}
                                <div style="position:relative">
                                    <i class="bi bi-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#999;z-index:1;pointer-events:none"></i>
                                    <input type="text"
                                           id="itemSearch"
                                           class="form-control"
                                           placeholder="Type name or part number to search…"
                                           autocomplete="off"
                                           style="padding-left:34px;padding-right:34px">
                                    <button type="button" id="itemClearBtn"
                                            onclick="clearPicker()"
                                            style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);
                                                   background:none;border:none;color:#aaa;font-size:1.1rem;line-height:1;padding:0;cursor:pointer"
                                            title="Clear selection">×</button>
                                </div>

                                {{-- Dropdown --}}
                                <div id="itemDropdown"
                                     style="display:none;position:absolute;z-index:1050;
                                            background:#fff;border:1.5px solid #d0d5dd;border-radius:10px;
                                            box-shadow:0 8px 28px rgba(0,0,0,0.13);
                                            max-height:300px;overflow-y:auto;width:100%;margin-top:4px">
                                    <div id="itemDropdownList"></div>
                                    <div id="itemDropdownEmpty"
                                         style="display:none;padding:16px;text-align:center;color:#888;font-size:0.875rem">
                                        <i class="bi bi-inbox me-2"></i>No items found
                                    </div>
                                </div>

                                {{-- Selected item summary card --}}
                                <div id="selectedCard"
                                     style="display:none;margin-top:8px;padding:10px 14px;border-radius:8px;
                                            background:#f0f7ff;border-left:4px solid var(--solar-orange)">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold" id="selName" style="font-size:0.9rem"></div>
                                            <div class="text-muted" id="selPn" style="font-size:0.76rem"></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold fs-5" id="selStock"></div>
                                            <div class="text-muted" style="font-size:0.72rem">warehouse stock</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Live stock preview --}}
                            <div class="col-12" id="stockInfoRow" style="display:none">
                                <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:#f8f9fa">
                                    <div>
                                        <span class="text-muted small">Current Stock:</span>
                                        <span class="fw-bold fs-5 ms-1" id="currentStockBadge">—</span>
                                        <span class="text-muted small" id="unitLabel"></span>
                                    </div>
                                    <div id="afterStockWrap" style="display:none">
                                        <span class="text-muted small">After transaction:</span>
                                        <span class="fw-bold fs-5 ms-1" id="afterStockBadge">—</span>
                                        <span class="text-muted small" id="afterUnitLabel"></span>
                                    </div>
                                    <div id="lowStockWarn" class="badge bg-warning text-dark ms-auto" style="display:none">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Low Stock
                                    </div>
                                </div>
                            </div>

                            {{-- Transaction Type --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">Transaction Type <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <div class="flex-fill" style="min-width:140px">
                                        <input type="radio" name="type" value="in" class="btn-check" id="typeIn"
                                               {{ old('type','in') === 'in' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-success w-100 py-3" for="typeIn">
                                            <i class="bi bi-arrow-down-circle d-block fs-4 mb-1"></i>
                                            <strong>Stock IN</strong>
                                            <div class="small fw-normal opacity-75">deploy to zone</div>
                                        </label>
                                    </div>
                                    <div class="flex-fill" style="min-width:140px">
                                        <input type="radio" name="type" value="out" class="btn-check" id="typeOut"
                                               {{ old('type') === 'out' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-danger w-100 py-3" for="typeOut">
                                            <i class="bi bi-arrow-up-circle d-block fs-4 mb-1"></i>
                                            <strong>Stock OUT</strong>
                                            <div class="small fw-normal opacity-75">return / isolate</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- OUT Reason --}}
                            <input type="hidden" name="out_reason" id="outReasonHidden" value="{{ old('out_reason','normal') }}">
                            <div class="col-12" id="outReasonRow" style="display:none">
                                <label class="form-label fw-semibold">Reason for Stock OUT <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <div class="flex-fill" style="min-width:110px">
                                        <input type="radio" name="_out_reason_ui" value="normal" class="btn-check" id="reasonNormal"
                                               {{ old('out_reason','normal') === 'normal' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-secondary w-100 py-2" for="reasonNormal">
                                            <i class="bi bi-arrow-return-left d-block fs-5 mb-1"></i>
                                            <strong class="small">Return</strong>
                                            <div class="small fw-normal opacity-75">back to warehouse</div>
                                        </label>
                                    </div>
                                    <div class="flex-fill" style="min-width:110px">
                                        <input type="radio" name="_out_reason_ui" value="defect" class="btn-check" id="reasonDefect"
                                               {{ old('out_reason') === 'defect' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-warning w-100 py-2" for="reasonDefect">
                                            <i class="bi bi-exclamation-triangle d-block fs-5 mb-1"></i>
                                            <strong class="small">Defect</strong>
                                            <div class="small fw-normal opacity-75">faulty / not working</div>
                                        </label>
                                    </div>
                                    <div class="flex-fill" style="min-width:110px">
                                        <input type="radio" name="_out_reason_ui" value="damaged" class="btn-check" id="reasonDamaged"
                                               {{ old('out_reason') === 'damaged' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-danger w-100 py-2" for="reasonDamaged">
                                            <i class="bi bi-heartbreak d-block fs-5 mb-1"></i>
                                            <strong class="small">Damaged</strong>
                                            <div class="small fw-normal opacity-75">physically broken</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Isolate notice --}}
                            <div class="col-12" id="isolateNotice" style="display:none">
                                <div class="alert border-0 py-2 px-3 mb-0" style="background:#fff3cd;border-left:4px solid #ffc107 !important">
                                    <i class="bi bi-shield-exclamation me-2 text-warning"></i>
                                    <strong>This item will be automatically added to Isolated Items.</strong>
                                    Stock will <strong>not</strong> be returned to warehouse.
                                </div>
                            </div>

                            {{-- Quantity + Notes --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="qtyInput"
                                       class="form-control" min="1" value="{{ old('quantity', 1) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="1"
                                          placeholder="Optional notes…">{{ old('notes') }}</textarea>
                            </div>

                            {{-- Isolation details --}}
                            <div id="isolateFields" style="display:none" class="col-12">
                                <div class="card border-warning">
                                    <div class="card-header py-2" style="background:#fff3cd">
                                        <span class="fw-semibold small"><i class="bi bi-shield-exclamation me-2"></i>Isolation Details</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-semibold small">Reason / Description <span class="text-danger">*</span></label>
                                                <textarea name="isolate_reason" id="isolateReason" class="form-control form-control-sm" rows="2"
                                                          placeholder="Describe the defect or damage…">{{ old('isolate_reason') }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold small">Additional Notes</label>
                                                <input type="text" name="isolate_notes" class="form-control form-control-sm"
                                                       placeholder="e.g. serial number, found at site…" value="{{ old('isolate_notes') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-flex gap-2 justify-content-end mt-2">
                                <a href="{{ route('zones.show', $zone) }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-solar px-4" id="submitBtn">
                                    <i class="bi bi-check-circle me-2"></i>Record Transaction
                                </button>
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
<script>
// ── Data ────────────────────────────────────────────────────
const ALL_ITEMS = {!! $itemsForJs !!};
let pickedItem  = null;

// ── DOM refs ────────────────────────────────────────────────
const searchEl   = document.getElementById('itemSearch');
const dropdown   = document.getElementById('itemDropdown');
const listEl     = document.getElementById('itemDropdownList');
const emptyEl    = document.getElementById('itemDropdownEmpty');
const clearBtn   = document.getElementById('itemClearBtn');
const hiddenId   = document.getElementById('hiddenItemId');
const selCard    = document.getElementById('selectedCard');
const qtyInput   = document.getElementById('qtyInput');
const submitBtn  = document.getElementById('submitBtn');

// ── Helpers ─────────────────────────────────────────────────
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function getType()      { const r = document.querySelector('input[name="type"]:checked'); return r ? r.value : 'in'; }
function getOutReason() { return document.getElementById('outReasonHidden').value || 'normal'; }

// ── Render dropdown list ─────────────────────────────────────
function renderDropdown(query) {
    const q = (query || '').toLowerCase().trim();
    const hits = q
        ? ALL_ITEMS.filter(i =>
            i.name.toLowerCase().includes(q) ||
            i.pn.toLowerCase().includes(q))
        : ALL_ITEMS;

    listEl.innerHTML = '';

    if (hits.length === 0) {
        emptyEl.style.display = '';
        return;
    }
    emptyEl.style.display = 'none';

    hits.forEach(function(item, idx) {
        const out  = item.stock <= 0;
        const low  = !out && item.stock <= item.min;
        const col  = out ? '#dc3545' : low ? '#d97706' : '#16a34a';
        const bg   = out ? '#fff1f2' : low ? '#fffbeb' : '#f0fdf4';
        const icon = out ? '⛔' : low ? '⚠️' : '✅';

        const row = document.createElement('div');
        row.setAttribute('role','option');
        row.style.cssText =
            'display:flex;align-items:center;justify-content:space-between;gap:12px;' +
            'padding:9px 14px;cursor:pointer;border-bottom:1px solid #f3f4f6;' +
            'transition:background 0.1s;' +
            (idx === 0 ? 'border-radius:8px 8px 0 0;' : '');

        row.innerHTML =
            '<div style="min-width:0;flex:1">' +
                '<div style="font-weight:600;font-size:0.875rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(item.name) + '</div>' +
                '<div style="font-size:0.75rem;color:#6b7280;margin-top:1px">' + (item.pn ? esc(item.pn) : '<em>no part number</em>') + '</div>' +
            '</div>' +
            '<div style="flex-shrink:0;text-align:right;background:' + bg + ';' +
                 'border-radius:6px;padding:4px 9px;border:1px solid ' + col + '33">' +
                '<div style="font-weight:700;color:' + col + ';font-size:0.875rem;line-height:1.2">' + item.stock + ' ' + esc(item.unit) + '</div>' +
                '<div style="font-size:0.7rem;color:' + col + '">' + icon + ' ' + (out ? 'Out of stock' : low ? 'Low stock' : 'Available') + '</div>' +
            '</div>';

        row.addEventListener('mouseenter', function() { this.style.background = '#f5f3ff'; });
        row.addEventListener('mouseleave', function() { this.style.background = ''; });
        row.addEventListener('mousedown',  function(e) { e.preventDefault(); pickItem(item); });
        listEl.appendChild(row);
    });
}

// ── Pick an item ─────────────────────────────────────────────
function pickItem(item) {
    pickedItem = item;
    hiddenId.value = item.id;

    // Fill search box
    searchEl.value = (item.pn ? '[' + item.pn + '] ' : '') + item.name;
    clearBtn.style.display = '';
    dropdown.style.display = 'none';

    // Show summary card
    const out = item.stock <= 0;
    const low = !out && item.stock <= item.min;
    const col = out ? '#dc3545' : low ? '#d97706' : '#16a34a';
    selCard.style.display = '';
    document.getElementById('selName').textContent  = item.name;
    document.getElementById('selPn').textContent    = item.pn ? 'Part No: ' + item.pn : '';
    document.getElementById('selStock').textContent = item.stock + ' ' + item.unit;
    document.getElementById('selStock').style.color = col;

    updateUI();
}

// ── Clear picker ─────────────────────────────────────────────
function clearPicker() {
    pickedItem = null;
    hiddenId.value = '';
    searchEl.value = '';
    clearBtn.style.display  = 'none';
    selCard.style.display   = 'none';
    dropdown.style.display  = 'none';
    searchEl.focus();
    updateUI();
}

// ── Search input events ──────────────────────────────────────
searchEl.addEventListener('focus', function() {
    renderDropdown(this.value);
    dropdown.style.display = '';
});
searchEl.addEventListener('input', function() {
    if (!this.value) { clearPicker(); return; }
    renderDropdown(this.value);
    dropdown.style.display = '';
    // Reset picked item when user types
    pickedItem = null;
    hiddenId.value = '';
    selCard.style.display = 'none';
});
searchEl.addEventListener('blur', function() {
    setTimeout(function() { dropdown.style.display = 'none'; }, 180);
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') dropdown.style.display = 'none';
});

// ── Restore old value after validation failure ───────────────
(function() {
    const old = '{{ old("item_id") }}';
    if (old) {
        const found = ALL_ITEMS.find(function(i) { return String(i.id) === String(old); });
        if (found) pickItem(found);
    }
})();

// ── Update form UI ───────────────────────────────────────────
function updateUI() {
    const type      = getType();
    const reason    = getOutReason();
    const isOut     = type === 'out';
    const isIsolate = isOut && (reason === 'defect' || reason === 'damaged');

    document.getElementById('outReasonRow').style.display  = isOut     ? '' : 'none';
    document.getElementById('isolateNotice').style.display = isIsolate ? '' : 'none';
    document.getElementById('isolateFields').style.display = isIsolate ? '' : 'none';
    document.getElementById('isolateReason').required      = isIsolate;

    if (!pickedItem) {
        document.getElementById('stockInfoRow').style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Record Transaction';
        submitBtn.className = 'btn btn-solar px-4';
        return;
    }

    document.getElementById('stockInfoRow').style.display = '';
    const stock = pickedItem.stock;
    const qty   = parseInt(qtyInput.value) || 0;
    const unit  = pickedItem.unit;
    const min   = pickedItem.min;

    document.getElementById('currentStockBadge').textContent = stock;
    document.getElementById('unitLabel').textContent         = unit;
    document.getElementById('afterUnitLabel').textContent    = unit;
    document.getElementById('lowStockWarn').style.display    = stock <= min ? '' : 'none';

    if (qty > 0) {
        document.getElementById('afterStockWrap').style.display = '';
        const after = type === 'in' ? stock - qty : isIsolate ? stock : stock + qty;
        const badge = document.getElementById('afterStockBadge');
        badge.textContent = after;
        badge.className   = 'fw-bold fs-5 ms-1 ' + (after < 0 ? 'text-danger' : after <= min ? 'text-warning' : 'text-success');
    } else {
        document.getElementById('afterStockWrap').style.display = 'none';
    }

    if (type === 'in' && qty > stock) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-x-circle me-2"></i>Insufficient stock';
        submitBtn.className = 'btn btn-secondary px-4';
    } else {
        submitBtn.disabled = false;
        const lbl  = isIsolate ? 'Isolate Item' : 'Record Transaction';
        const icon = isIsolate ? 'bi-shield-exclamation' : 'bi-check-circle';
        submitBtn.innerHTML = `<i class="bi ${icon} me-2"></i>${lbl}`;
        submitBtn.className = 'btn btn-solar px-4';
    }
}

// ── Wire up reason radios & other inputs ─────────────────────
document.querySelectorAll('input[name="_out_reason_ui"]').forEach(function(r) {
    r.addEventListener('change', function() {
        document.getElementById('outReasonHidden').value = this.value;
        updateUI();
    });
});
document.querySelectorAll('input[name="type"]').forEach(function(r) {
    r.addEventListener('change', updateUI);
});
qtyInput.addEventListener('input', updateUI);

// ── Prevent form submit without item selected ────────────────
document.getElementById('stockForm').addEventListener('submit', function(e) {
    if (!hiddenId.value) {
        e.preventDefault();
        searchEl.focus();
        searchEl.style.borderColor = '#dc3545';
        searchEl.style.boxShadow   = '0 0 0 0.2rem rgba(220,53,69,0.25)';
        setTimeout(function() {
            searchEl.style.borderColor = '';
            searchEl.style.boxShadow   = '';
        }, 2000);
    }
});

updateUI();
</script>
@endsection
