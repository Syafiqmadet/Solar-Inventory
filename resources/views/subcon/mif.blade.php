@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'MIF — ' . $subcon->name)
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('subcon.index') }}" class="btn btn-sm btn-outline-light mb-2">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <h1><i class="bi bi-box-arrow-right me-2"></i>Material Issue Form</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">
                <strong>{{ $subcon->name }}</strong>
                @if($subcon->zone) · {{ $subcon->zone->name }} @endif
                &nbsp;·&nbsp; {!! $subcon->status_badge !!}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('subcon.mif.export', $subcon) }}" class="btn btn-success fw-semibold">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            </a>
            @if($canWrite)
            <button class="btn btn-light fw-semibold" data-bs-toggle="modal" data-bs-target="#modalMif">
                <i class="bi bi-plus-circle me-2"></i>New MIF
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
        <div class="col-md-4">
            <div class="card text-center p-3">
                <h4 class="text-primary mb-0">{{ $mifs->count() }}</h4>
                <small class="text-muted">Total MIF Forms</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3">
                <h4 class="text-danger mb-0">{{ $mifs->sum(fn($m) => $m->items->sum('quantity')) }}</h4>
                <small class="text-muted">Total Qty Issued</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3">
                <h4 class="text-warning mb-0">{{ $mifs->sum(fn($m) => $m->items->count()) }}</h4>
                <small class="text-muted">Total Line Items</small>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-3">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="mifSearch" class="form-control" placeholder="Search by MIF number...">
            <button class="btn btn-outline-secondary" onclick="document.getElementById('mifSearch').value='';filterMif('')"><i class="bi bi-x"></i></button>
        </div>
        <div id="mifNoResult" class="text-center text-muted py-3" style="display:none">No MIF found matching your search.</div>
    </div>

    @forelse($mifs as $mif)
    <div class="card mb-3 mif-card" data-number="{{ strtolower($mif->mif_number) }}">
        <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:#f8f9fa">
            <div>
                <span class="badge bg-primary me-2">{{ $mif->mif_number }}</span>
                <span class="fw-semibold">{{ $mif->date->format('d M Y') }}</span>
                <span class="text-muted small ms-2">Issued by: {{ $mif->issuedBy?->name ?? '—' }}</span>
                @if($mif->notes)<span class="text-muted small ms-3"><i class="bi bi-chat me-1"></i>{{ $mif->notes }}</span>@endif
            </div>
            @if($canWrite)
            <form method="POST" action="{{ route('subcon.mif.destroy', [$subcon, $mif]) }}"
                onsubmit="return confirm('Delete this MIF? Stock will be restored.')">
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
                        <th style="width:28%">Item Name</th>
                        <th style="width:16%">Part No.</th>
                        <th class="text-center" style="width:80px">Qty</th>
                        <th style="width:80px">Unit</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($mif->items as $i => $row)
                <tr>
                    <td class="px-3 text-muted small">{{ $i+1 }}</td>
                    <td class="fw-semibold">{{ $row->item_name }}</td>
                    <td><code class="small">{{ $row->part_number ?? '—' }}</code></td>
                    <td class="text-center text-danger fw-bold">{{ $row->quantity }}</td>
                    <td class="small">{{ $row->unit ?? '—' }}</td>
                    <td class="small text-muted">{{ $row->remarks ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="table-light fw-semibold">
                    <td colspan="3" class="px-3 text-end">Total</td>
                    <td class="text-center text-danger">{{ $mif->items->sum('quantity') }}</td>
                    <td colspan="2"></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-5">
        <i class="bi bi-box-arrow-right display-5 d-block mb-2 opacity-25"></i>
        No MIF records yet. Click <strong>New MIF</strong> to issue materials.
    </div>
    @endforelse
</div>

{{-- New MIF Modal --}}
@if($canWrite)
<div class="modal fade" id="modalMif" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--solar-dark);color:#fff">
                <h5 class="modal-title"><i class="bi bi-box-arrow-right me-2"></i>New Material Issue Form</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('subcon.mif.store', $subcon) }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">MIF Number <span class="text-danger">*</span></label>
                            <input type="text" id="mifNumberInput" name="mif_number" class="form-control" placeholder="e.g. MIF-2025-001" required autocomplete="off">
                            <span id="mifNumberFeedback" class="form-text"></span>
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

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Items to Issue</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addMifRow">
                            <i class="bi bi-plus me-1"></i>Add Row
                        </button>
                    </div>

                    <div class="table-responsive">
                    <table class="table table-bordered" id="mifTable">
                        <thead class="table-light fw-bold">
                            <tr>
                                <th style="width:28%">Item Name <span class="text-danger">*</span></th>
                                <th style="width:16%">Part No.</th>
                                <th style="width:9%">Qty <span class="text-danger">*</span></th>
                                <th style="width:9%">Unit</th>
                                <th>Remarks</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="mifRows">
                            <tr class="mif-row">
                                <td>
                                    <select name="rows[0][item_id]" class="form-select form-select-sm item-select">
                                        <option value="">— Custom / Manual —</option>
                                        @foreach($items as $item)
                                        <option value="{{ $item->id }}" data-name="{{ $item->name }}" data-part="{{ $item->part_number }}" data-unit="{{ $item->unit }}">
                                            {{ $item->name }} @if($item->part_number)({{ $item->part_number }})@endif
                                        </option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="rows[0][item_name]" class="form-control form-control-sm mt-1 item-name" placeholder="Item name *" required>
                                </td>
                                <td><input type="text" name="rows[0][part_number]" class="form-control form-control-sm item-part" placeholder="Part no."></td>
                                <td><input type="number" name="rows[0][quantity]" class="form-control form-control-sm" step="0.01" min="0.01" required></td>
                                <td><input type="text" name="rows[0][unit]" class="form-control form-control-sm item-unit" placeholder="pcs"></td>
                                <td><input type="text" name="rows[0][remarks]" class="form-control form-control-sm" placeholder="Optional"></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="mifSubmitBtn" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Submit MIF</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
let mifRowIndex = 1;

const mifItemOptions = `@foreach($items as $item)<option value="{{ $item->id }}" data-name="{{ $item->name }}" data-part="{{ $item->part_number }}" data-unit="{{ $item->unit }}">{{ $item->name }}@if($item->part_number) ({{ $item->part_number }})@endif</option>@endforeach`;

function bindItemSelect(row) {
    row.querySelector('.item-select').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt.value) {
            row.querySelector('.item-name').value = opt.dataset.name || '';
            row.querySelector('.item-part').value = opt.dataset.part || '';
            row.querySelector('.item-unit').value = opt.dataset.unit || '';
        }
    });
    row.querySelector('.remove-row').addEventListener('click', function() {
        if (document.querySelectorAll('.mif-row').length > 1) this.closest('tr').remove();
    });
}

bindItemSelect(document.querySelector('.mif-row'));

document.getElementById('addMifRow').addEventListener('click', function() {
    const tbody = document.getElementById('mifRows');
    const idx   = mifRowIndex++;
    const tr    = document.createElement('tr');
    tr.className = 'mif-row';
    tr.innerHTML = `
        <td>
            <select name="rows[${idx}][item_id]" class="form-select form-select-sm item-select">
                <option value="">— Custom / Manual —</option>
                ${mifItemOptions}
            </select>
            <input type="text" name="rows[${idx}][item_name]" class="form-control form-control-sm mt-1 item-name" placeholder="Item name *" required>
        </td>
        <td><input type="text" name="rows[${idx}][part_number]" class="form-control form-control-sm item-part" placeholder="Part no."></td>
        <td><input type="number" name="rows[${idx}][quantity]" class="form-control form-control-sm" step="0.01" min="0.01" required></td>
        <td><input type="text" name="rows[${idx}][unit]" class="form-control form-control-sm item-unit" placeholder="pcs"></td>
        <td><input type="text" name="rows[${idx}][remarks]" class="form-control form-control-sm" placeholder="Optional"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
    bindItemSelect(tr);
});
</script>

<script>
function filterMif(q) {
    q = q.toLowerCase().trim();
    let found = 0;
    document.querySelectorAll('.mif-card').forEach(card => {
        const match = !q || card.dataset.number.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) found++;
    });
    document.getElementById('mifNoResult').style.display = (q && found === 0) ? '' : 'none';
}
document.getElementById('mifSearch').addEventListener('input', function() { filterMif(this.value); });
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
<script>checkFormNumber('mifNumberInput','mifNumberFeedback','mifSubmitBtn');</script>
@endsection
