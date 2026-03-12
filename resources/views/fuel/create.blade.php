@extends('layouts.app')
@section('title', 'Add Fuel Record')

@section('styles')
<style>
.fuel-toggle { display:none; }
.fuel-toggle + label {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding: 20px 24px; border: 2px solid #dee2e6; border-radius: 12px;
    cursor: pointer; transition: all .2s; min-width: 140px; gap: 6px;
}
.fuel-toggle + label .icon { font-size: 2rem; }
.fuel-toggle + label .label { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
#type_petrol:checked + label { border-color: #28a745; background: #e8f8ee; color: #28a745; }
#type_diesel:checked + label { border-color: #0d6efd; background: #e8f0ff; color: #0d6efd; }
.drop-zone {
    border: 2px dashed #dee2e6; border-radius: 12px; padding: 32px 20px;
    text-align: center; cursor: pointer; transition: all .2s; background: #fafafa;
}
.drop-zone.drag-over { border-color: #FF6B35; background: #fff5f0; }
.drop-zone:hover { border-color: #aaa; }
#imagePreview { display:none; }
#imagePreview img { max-height: 220px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1><i class="bi bi-fuel-pump me-2"></i>Add Fuel Record</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Record a new petrol or diesel purchase</p>
</div>

<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form action="{{ route('fuel.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Fuel Type Selector --}}
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-fuel-pump me-2 text-warning"></i>Fuel Type</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex gap-4">
                            <div>
                                <input type="radio" name="fuel_type" value="petrol"
                                       class="fuel-toggle" id="type_petrol"
                                       {{ old('fuel_type','petrol') === 'petrol' ? 'checked' : '' }}>
                                <label for="type_petrol">
                                    <span class="icon">⛽</span>
                                    <span class="label">Petrol</span>
                                </label>
                            </div>
                            <div>
                                <input type="radio" name="fuel_type" value="diesel"
                                       class="fuel-toggle" id="type_diesel"
                                       {{ old('fuel_type') === 'diesel' ? 'checked' : '' }}>
                                <label for="type_diesel">
                                    <span class="icon">🚛</span>
                                    <span class="label">Diesel</span>
                                </label>
                            </div>
                        </div>
                        @error('fuel_type')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Purchase Details --}}
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-receipt me-2 text-info"></i>Purchase Details</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                       value="{{ old('date', date('Y-m-d')) }}" required>
                                @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Liters (L) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="liters" id="liters"
                                           class="form-control @error('liters') is-invalid @enderror"
                                           value="{{ old('liters') }}" step="0.01" min="0.01"
                                           placeholder="0.00" required>
                                    <span class="input-group-text">L</span>
                                </div>
                                @error('liters')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Amount (RM) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">RM</span>
                                    <input type="number" name="amount_rm" id="amount_rm"
                                           class="form-control @error('amount_rm') is-invalid @enderror"
                                           value="{{ old('amount_rm') }}" step="0.01" min="0.01"
                                           placeholder="0.00" required>
                                </div>
                                @error('amount_rm')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Live RM/L calculator --}}
                            <div class="col-12">
                                <div id="rateDisplay" class="d-none p-3 rounded text-center" style="background:#f0f9ff;border:1px solid #bee3f8">
                                    <span class="text-muted small">Price per liter: </span>
                                    <span id="rateValue" class="fw-bold text-primary fs-5">RM 0.0000</span>
                                    <span class="text-muted small"> /L</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">DO Number</label>
                                <input type="text" name="do_number"
                                       class="form-control @error('do_number') is-invalid @enderror"
                                       value="{{ old('do_number') }}" placeholder="e.g. DO-2024-00123">
                                @error('do_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Supplier</label>
                                <input type="text" name="supplier" class="form-control"
                                       value="{{ old('supplier') }}" placeholder="Station / supplier name">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Vehicle No.</label>
                                <input type="text" name="vehicle_no" class="form-control"
                                       value="{{ old('vehicle_no') }}" placeholder="e.g. WXX 1234">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Optional remarks...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DO Image Upload --}}
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-image me-2 text-success"></i>Delivery Order (DO) Image</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="drop-zone" id="dropZone" onclick="document.getElementById('do_image').click()">
                            <input type="file" name="do_image" id="do_image"
                                   accept="image/*" class="d-none" @error('do_image') @enderror>
                            <div id="dropPrompt">
                                <i class="bi bi-cloud-upload text-muted" style="font-size:2.5rem"></i>
                                <p class="mt-2 mb-1 fw-semibold text-muted">Click or drag &amp; drop DO image here</p>
                                <small class="text-muted">JPG, PNG, GIF, WEBP — max 5MB</small>
                            </div>
                            <div id="imagePreview" class="mt-2">
                                <img id="previewImg" src="" alt="Preview">
                                <p class="mt-2 text-muted small" id="fileName"></p>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="clearImage(event)">
                                    <i class="bi bi-x me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                        @error('do_image')
                            <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('fuel.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-solar px-5">
                        <i class="bi bi-save me-2"></i>Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Live price-per-liter calculator
function calcRate() {
    const L = parseFloat(document.getElementById('liters').value) || 0;
    const RM = parseFloat(document.getElementById('amount_rm').value) || 0;
    const disp = document.getElementById('rateDisplay');
    const val  = document.getElementById('rateValue');
    if (L > 0 && RM > 0) {
        disp.classList.remove('d-none');
        val.textContent = 'RM ' + (RM / L).toFixed(4);
    } else {
        disp.classList.add('d-none');
    }
}
document.getElementById('liters').addEventListener('input', calcRate);
document.getElementById('amount_rm').addEventListener('input', calcRate);

// Image preview & drag-drop
const input    = document.getElementById('do_image');
const dropZone = document.getElementById('dropZone');
const preview  = document.getElementById('imagePreview');
const prompt   = document.getElementById('dropPrompt');
const img      = document.getElementById('previewImg');
const fName    = document.getElementById('fileName');

input.addEventListener('change', () => showPreview(input.files[0]));

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
    }
});

function showPreview(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        img.src = e.target.result;
        fName.textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
        preview.style.display = 'block';
        prompt.style.display  = 'none';
    };
    reader.readAsDataURL(file);
}

function clearImage(e) {
    e.stopPropagation();
    input.value = '';
    preview.style.display = 'none';
    prompt.style.display  = 'block';
    img.src = '';
}
</script>
@endsection
