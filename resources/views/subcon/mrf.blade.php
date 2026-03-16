@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'MRF — ' . $subcon->name)
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('subcon.index') }}" class="btn btn-sm btn-outline-light mb-2">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <h1><i class="bi bi-box-arrow-in-left me-2"></i>Material Return Form</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">
                <strong>{{ $subcon->name }}</strong>
                @if($subcon->zone) · {{ $subcon->zone->name }} @endif
                &nbsp;·&nbsp; {!! $subcon->status_badge !!}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('subcon.mrf.export', $subcon) }}" class="btn btn-success fw-semibold">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            </a>
            @if($canWrite)
            <button class="btn btn-light fw-semibold" data-bs-toggle="modal" data-bs-target="#modalMrf">
                <i class="bi bi-plus-circle me-2"></i>New MRF
            </button>
            @endif
        </div>
    </div>
</div>

<div class="content-area">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-info mb-0">{{ $mrfs->count() }}</h4>
                <small class="text-muted">Total MRF Forms</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-success mb-0">{{ $mrfs->sum(fn($m) => $m->items->where('condition','good')->sum('quantity')) }}</h4>
                <small class="text-muted">Qty Returned (Good)</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-danger mb-0">{{ $mrfs->sum(fn($m) => $m->items->whereIn('condition',['damaged','defect'])->sum('quantity')) }}</h4>
                <small class="text-muted">Qty Damaged → Isolated</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-warning mb-0">{{ $mrfs->sum(fn($m) => $m->items->count()) }}</h4>
                <small class="text-muted">Total Line Items</small>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-3">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="mrfSearch" class="form-control" placeholder="Search by MRF number...">
            <button class="btn btn-outline-secondary" onclick="document.getElementById('mrfSearch').value='';filterMrf('')"><i class="bi bi-x"></i></button>
        </div>
        <div id="mrfNoResult" class="text-center text-muted py-3" style="display:none">No MRF found matching your search.</div>
    </div>

    @forelse($mrfs as $mrf)
    <div class="card mb-3 mrf-card" data-number="{{ strtolower($mrf->mrf_number) }}">
        <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:#f8f9fa">
            <div>
                <span class="badge bg-info text-dark me-2">{{ $mrf->mrf_number }}</span>
                <span class="fw-semibold">{{ $mrf->date->format('d M Y') }}</span>
                @if($mrf->notes)<span class="text-muted small ms-3"><i class="bi bi-chat me-1"></i>{{ $mrf->notes }}</span>@endif
            </div>
            @if($canWrite)
            <form method="POST" action="{{ route('subcon.mrf.destroy', [$subcon, $mrf]) }}"
                onsubmit="return confirm('Delete this MRF? Good items will be deducted from stock.')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
            @endif
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light fw-bold">
                    <tr>
                        <th class="px-3" style="width:40px">#</th>
                        <th style="width:25%">Item Name</th>
                        <th style="width:14%">Part No.</th>
                        <th class="text-center" style="width:70px">Qty</th>
                        <th style="width:70px">Unit</th>
                        <th class="text-center" style="width:110px">Condition</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($mrf->items as $i => $row)
                <tr class="{{ in_array($row->condition, ['damaged','defect']) ? 'table-danger' : '' }}">
                    <td class="px-3 text-muted small">{{ $i+1 }}</td>
                    <td class="fw-semibold">{{ $row->item_name }}</td>
                    <td><code class="small">{{ $row->part_number ?? '—' }}</code></td>
                    <td class="text-center fw-bold {{ $row->condition === 'good' ? 'text-success' : 'text-danger' }}">
                        {{ $row->quantity }}
                    </td>
                    <td class="small">{{ $row->unit ?? '—' }}</td>
                    <td class="text-center">
                        @if($row->condition === 'good')
                            <span class="badge bg-success"><i class="bi bi-check me-1"></i>Good</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Damaged</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $row->remarks ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="table-light fw-semibold">
                    <td colspan="3" class="px-3 text-end">Total</td>
                    <td class="text-center">{{ $mrf->items->sum('quantity') }}</td>
                    <td colspan="3"></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-5">
        <i class="bi bi-box-arrow-in-left display-5 d-block mb-2 opacity-25"></i>
        No MRF records yet. Click <strong>New MRF</strong> to record returns.
    </div>
    @endforelse
</div>

{{-- New MRF Modal --}}
@if($canWrite)
<div class="modal fade" id="modalMrf" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--solar-dark);color:#fff">
                <h5 class="modal-title"><i class="bi bi-box-arrow-in-left me-2"></i>New Material Return Form</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('subcon.mrf.store', $subcon) }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">MRF Number <span class="text-danger">*</span></label>
                            <input type="text" id="mrfNumberInput" name="mrf_number" class="form-control" placeholder="e.g. MRF-2025-001" required autocomplete="off">
                            <span id="mrfNumberFeedback" class="form-text"></span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional remarks">
                        </div>
                    </div>
                    @if($mifItems->isEmpty())
                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>No items available to return.</strong> All MIF-issued items have already been returned, or no MIF has been issued to this subcon yet.
                    </div>
                    @endif
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Only items previously issued via <strong>MIF</strong> can be returned. Available quantity is shown per item.
                        <strong>Good</strong> → returned to stock. <strong>Damaged / Defect</strong> → sent to <em>Isolated Items</em>.
                    </div>


                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Items Returned</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addMrfRow">
                            <i class="bi bi-plus me-1"></i>Add Row
                        </button>
                    </div>

                    <div class="table-responsive">
                    <table class="table table-bordered" id="mrfTable">
                        <thead class="table-light fw-bold">
                            <tr>
                                <th style="width:28%">Item Name <span class="text-danger">*</span></th>
                                <th style="width:14%">Part No.</th>
                                <th style="width:9%">Qty <span class="text-danger">*</span></th>
                                <th style="width:8%">Unit</th>
                                <th style="width:13%">Condition <span class="text-danger">*</span></th>
                                <th>Remarks</th>
                                <th style="width:160px">Proof Images <small class="text-muted">(damaged/defect)</small></th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="mrfRows">
                            <tr class="mrf-row">
                                <td>
                                    <select name="rows[0][item_id]" class="form-select form-select-sm item-select">
                                        <option value="">— Select MIF Item —</option>
                                        @foreach($mifItems as $item)
                                        <option value="{{ $item->id }}" data-name="{{ $item->name }}" data-part="{{ $item->part_number }}" data-unit="{{ $item->unit }}" data-max="{{ $item->remaining }}">
                                            {{ $item->name }}@if($item->part_number) ({{ $item->part_number }})@endif — Avail: {{ $item->remaining }} {{ $item->unit }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="rows[0][item_name]" class="form-control form-control-sm mt-1 item-name" placeholder="Item name *" required>
                                </td>
                                <td><input type="text" name="rows[0][part_number]" class="form-control form-control-sm item-part" placeholder="Part no."></td>
                                <td><input type="number" name="rows[0][quantity]" class="form-control form-control-sm" step="0.01" min="0.01" required></td>
                                <td><input type="text" name="rows[0][unit]" class="form-control form-control-sm item-unit" placeholder="pcs"></td>
                                <td>
                                    <select name="rows[0][condition]" class="form-select form-select-sm condition-select" required>
                                        <option value="good">✅ Good</option>
                                        <option value="damaged">⚠️ Damaged</option>
                                        <option value="defect">🔧 Defect</option>
                                    </select>
                                </td>
                                <td><input type="text" name="rows[0][remarks]" class="form-control form-control-sm" placeholder="Optional"></td>
                                <td class="proof-cell">
                                    <div class="proof-upload d-none">
                                        <div class="d-flex gap-1 flex-wrap">
                                            <label class="proof-thumb-label" title="Image 1">
                                                <input type="file" class="proof-file d-none" accept="image/*">
                                                <input type="hidden" name="rows[0][proof_images][]" class="proof-b64">
                                                <div class="proof-preview border rounded d-flex align-items-center justify-content-center bg-light" style="width:44px;height:44px;cursor:pointer;font-size:18px">📷</div>
                                            </label>
                                            <label class="proof-thumb-label" title="Image 2">
                                                <input type="file" class="proof-file d-none" accept="image/*">
                                                <input type="hidden" name="rows[0][proof_images][]" class="proof-b64">
                                                <div class="proof-preview border rounded d-flex align-items-center justify-content-center bg-light" style="width:44px;height:44px;cursor:pointer;font-size:18px">📷</div>
                                            </label>
                                            <label class="proof-thumb-label" title="Image 3">
                                                <input type="file" class="proof-file d-none" accept="image/*">
                                                <input type="hidden" name="rows[0][proof_images][]" class="proof-b64">
                                                <div class="proof-preview border rounded d-flex align-items-center justify-content-center bg-light" style="width:44px;height:44px;cursor:pointer;font-size:18px">📷</div>
                                            </label>
                                        </div>
                                        <small class="text-muted">Max 3 photos</small>
                                    </div>
                                </td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="mrfSubmitBtn" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Submit MRF</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
let mrfRowIndex = 1;

const mrfItemOptions = `<option value="">— Select MIF Item —</option>@foreach($mifItems as $item)<option value="{{ $item->id }}" data-name="{{ $item->name }}" data-part="{{ $item->part_number }}" data-unit="{{ $item->unit }}" data-max="{{ $item->remaining }}">{{ $item->name }}@if($item->part_number) ({{ $item->part_number }})@endif — Avail: {{ $item->remaining }} {{ $item->unit }}</option>@endforeach`;

function bindMrfRow(row) {
    row.querySelector('.item-select').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt.value) {
            row.querySelector('.item-name').value = opt.dataset.name || '';
            row.querySelector('.item-part').value = opt.dataset.part || '';
            row.querySelector('.item-unit').value = opt.dataset.unit || '';
            const qtyInput = row.querySelector('input[name*="[quantity]"]');
            qtyInput.max = opt.dataset.max || '';
            qtyInput.placeholder = 'Max: ' + (opt.dataset.max || '');
        } else {
            const qtyInput = row.querySelector('input[name*="[quantity]"]');
            qtyInput.max = '';
            qtyInput.placeholder = '';
        }
    });
    row.querySelector('.condition-select').addEventListener('change', function() {
        const isDmg = ['damaged','defect'].includes(this.value);
        row.style.background = isDmg ? '#fff5f5' : '';
        const proofDiv = row.querySelector('.proof-upload');
        if (proofDiv) proofDiv.classList.toggle('d-none', !isDmg);
    });
    // Bind proof file inputs
    row.querySelectorAll('.proof-thumb-label').forEach(label => {
        const fileInput = label.querySelector('.proof-file');
        const b64Input  = label.querySelector('.proof-b64');
        const preview   = label.querySelector('.proof-preview');
        fileInput.addEventListener('change', function() {
            if (!this.files[0]) return;
            const reader = new FileReader();
            reader.onload = function(ev) {
                b64Input.value = ev.target.result;
                preview.innerHTML = '<img src="' + ev.target.result + '" style="width:44px;height:44px;object-fit:cover;border-radius:4px">';
            };
            reader.readAsDataURL(this.files[0]);
        });
    });
    row.querySelector('.remove-row').addEventListener('click', function() {
        if (document.querySelectorAll('.mrf-row').length > 1) this.closest('tr').remove();
    });
}

// Bind on page load
bindMrfRow(document.querySelector('.mrf-row'));

// Re-bind when modal opens to ensure DOM is ready
document.getElementById('modalMrf').addEventListener('shown.bs.modal', function() {
    document.querySelectorAll('.mrf-row').forEach(row => {
        // Remove old listeners by cloning condition-select
        const oldSel = row.querySelector('.condition-select');
        if (oldSel) {
            const newSel = oldSel.cloneNode(true);
            oldSel.parentNode.replaceChild(newSel, oldSel);
        }
        bindMrfRow(row);
    });
});

document.getElementById('addMrfRow').addEventListener('click', function() {
    const tbody = document.getElementById('mrfRows');
    const idx   = mrfRowIndex++;
    const tr    = document.createElement('tr');
    tr.className = 'mrf-row';
    tr.innerHTML = `
        <td>
            <select name="rows[${idx}][item_id]" class="form-select form-select-sm item-select">
                <option value="">— Custom / Manual —</option>
                ${mrfItemOptions}
            </select>
            <input type="text" name="rows[${idx}][item_name]" class="form-control form-control-sm mt-1 item-name" placeholder="Item name *" required>
        </td>
        <td><input type="text" name="rows[${idx}][part_number]" class="form-control form-control-sm item-part" placeholder="Part no."></td>
        <td><input type="number" name="rows[${idx}][quantity]" class="form-control form-control-sm" step="0.01" min="0.01" required></td>
        <td><input type="text" name="rows[${idx}][unit]" class="form-control form-control-sm item-unit" placeholder="pcs"></td>
        <td>
            <select name="rows[${idx}][condition]" class="form-select form-select-sm condition-select" required>
                <option value="good">✅ Good</option>
                <option value="damaged">⚠️ Damaged</option>
                <option value="defect">🔧 Defect</option>
            </select>
        </td>
        <td><input type="text" name="rows[${idx}][remarks]" class="form-control form-control-sm" placeholder="Optional"></td>
        <td class="proof-cell">
            <div class="proof-upload d-none">
                <div class="d-flex gap-1 flex-wrap">
                    <label class="proof-thumb-label"><input type="file" class="proof-file d-none" accept="image/*"><input type="hidden" name="rows[${idx}][proof_images][]" class="proof-b64"><div class="proof-preview border rounded d-flex align-items-center justify-content-center bg-light" style="width:44px;height:44px;cursor:pointer;font-size:18px">📷</div></label>
                    <label class="proof-thumb-label"><input type="file" class="proof-file d-none" accept="image/*"><input type="hidden" name="rows[${idx}][proof_images][]" class="proof-b64"><div class="proof-preview border rounded d-flex align-items-center justify-content-center bg-light" style="width:44px;height:44px;cursor:pointer;font-size:18px">📷</div></label>
                    <label class="proof-thumb-label"><input type="file" class="proof-file d-none" accept="image/*"><input type="hidden" name="rows[${idx}][proof_images][]" class="proof-b64"><div class="proof-preview border rounded d-flex align-items-center justify-content-center bg-light" style="width:44px;height:44px;cursor:pointer;font-size:18px">📷</div></label>
                </div>
                <small class="text-muted">Max 3 photos</small>
            </div>
        </td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
    bindMrfRow(tr);
});
</script>

<script>
function filterMrf(q) {
    q = q.toLowerCase().trim();
    let found = 0;
    document.querySelectorAll('.mrf-card').forEach(card => {
        const match = !q || card.dataset.number.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) found++;
    });
    document.getElementById('mrfNoResult').style.display = (q && found === 0) ? '' : 'none';
}
document.getElementById('mrfSearch').addEventListener('input', function() { filterMrf(this.value); });
</script>

<script>
function checkFormNumber(inputId, feedbackId, submitId) {
    const input    = document.getElementById(inputId);
    const feedback = document.getElementById(feedbackId);
    const submit   = document.getElementById(submitId);
    if (!input) return;

    let timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        const val = this.value.trim();
        feedback.textContent = '';
        feedback.className   = 'form-text';
        submit.disabled      = false;

        if (!val) return;

        feedback.textContent = 'Checking...';
        timer = setTimeout(() => {
            fetch('{{ route("subcon.check.number") }}?number=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    if (data.taken) {
                        const where = data.in_mif ? 'MIF' : 'MRF';
                        feedback.textContent = '⚠️ This number already exists in ' + where + '. Please use a unique number.';
                        feedback.className   = 'form-text text-danger fw-semibold';
                        submit.disabled      = true;
                    } else {
                        feedback.textContent = '✅ Number available.';
                        feedback.className   = 'form-text text-success';
                        submit.disabled      = false;
                    }
                })
                .catch(() => { feedback.textContent = ''; submit.disabled = false; });
        }, 400);
    });
}
</script>
<script>checkFormNumber('mrfNumberInput','mrfNumberFeedback','mrfSubmitBtn');</script>
@endsection
