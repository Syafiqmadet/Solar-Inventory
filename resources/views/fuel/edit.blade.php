@extends('layouts.app')
@section('title', 'Edit Fuel Record')

@section('styles')
<style>
.fuel-toggle { display:none; }
.fuel-toggle + label {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding: 18px 22px; border: 2px solid #dee2e6; border-radius: 12px;
    cursor: pointer; transition: all .2s; min-width: 130px; gap: 6px;
}
.fuel-toggle + label .icon { font-size: 1.8rem; }
.fuel-toggle + label .label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
#type_petrol:checked + label { border-color: #28a745; background: #e8f8ee; color: #28a745; }
#type_diesel:checked + label { border-color: #0d6efd; background: #e8f0ff; color: #0d6efd; }
.drop-zone { border: 2px dashed #dee2e6; border-radius: 12px; padding: 24px 20px; text-align: center; cursor: pointer; transition: all .2s; background: #fafafa; }
.drop-zone:hover { border-color: #aaa; }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1><i class="bi bi-pencil me-2"></i>Edit Fuel Record</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">
        {{ $fuel->date->format('d M Y') }} — {{ ucfirst($fuel->fuel_type) }} — {{ number_format($fuel->liters,2) }}L
    </p>
</div>

<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form action="{{ route('fuel.update', $fuel) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-fuel-pump me-2 text-warning"></i>Fuel Type</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex gap-4">
                            <div>
                                <input type="radio" name="fuel_type" value="petrol" class="fuel-toggle" id="type_petrol"
                                       {{ old('fuel_type',$fuel->fuel_type)==='petrol' ? 'checked' : '' }}>
                                <label for="type_petrol"><span class="icon">⛽</span><span class="label">Petrol</span></label>
                            </div>
                            <div>
                                <input type="radio" name="fuel_type" value="diesel" class="fuel-toggle" id="type_diesel"
                                       {{ old('fuel_type',$fuel->fuel_type)==='diesel' ? 'checked' : '' }}>
                                <label for="type_diesel"><span class="icon">🚛</span><span class="label">Diesel</span></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-receipt me-2 text-info"></i>Purchase Details</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date *</label>
                                <input type="date" name="date" class="form-control"
                                       value="{{ old('date', $fuel->date->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Liters (L) *</label>
                                <div class="input-group">
                                    <input type="number" name="liters" id="liters" class="form-control"
                                           value="{{ old('liters', $fuel->liters) }}" step="0.01" min="0.01" required>
                                    <span class="input-group-text">L</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Amount (RM) *</label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">RM</span>
                                    <input type="number" name="amount_rm" id="amount_rm" class="form-control"
                                           value="{{ old('amount_rm', $fuel->amount_rm) }}" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div id="rateDisplay" class="p-2 rounded text-center" style="background:#f0f9ff;border:1px solid #bee3f8">
                                    <span class="text-muted small">Price per liter: </span>
                                    <span id="rateValue" class="fw-bold text-primary">RM {{ number_format($fuel->price_per_liter,4) }}</span>
                                    <span class="text-muted small"> /L</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">DO Number</label>
                                <input type="text" name="do_number" class="form-control"
                                       value="{{ old('do_number', $fuel->do_number) }}" placeholder="e.g. DO-2024-00123">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Supplier</label>
                                <input type="text" name="supplier" class="form-control"
                                       value="{{ old('supplier', $fuel->supplier) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Vehicle No.</label>
                                <input type="text" name="vehicle_no" class="form-control"
                                       value="{{ old('vehicle_no', $fuel->vehicle_no) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $fuel->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DO Image --}}
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0"><i class="bi bi-image me-2 text-success"></i>DO Image</h6>
                    </div>
                    <div class="card-body p-4">
                        @if($fuel->do_image)
                        <div class="mb-3 p-3 rounded d-flex align-items-center gap-3" style="background:#f8f9fa;border:1px solid #dee2e6">
                            <img src="{{ asset('storage/'.$fuel->do_image) }}"
                                 alt="Current DO" style="height:80px;border-radius:6px;border:1px solid #dee2e6">
                            <div>
                                <p class="mb-1 fw-semibold small">Current DO Image</p>
                                <p class="mb-2 text-muted small">Upload a new image below to replace, or</p>
                                <form action="{{ route('fuel.image.delete', $fuel) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Remove this image?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash me-1"></i>Remove Image
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif

                        <div class="drop-zone" onclick="document.getElementById('do_image').click()">
                            <input type="file" name="do_image" id="do_image" accept="image/*" class="d-none">
                            <div id="dropPrompt">
                                <i class="bi bi-cloud-upload text-muted" style="font-size:2rem"></i>
                                <p class="mt-2 mb-1 text-muted small">
                                    {{ $fuel->do_image ? 'Click to upload replacement image' : 'Click or drag & drop DO image' }}
                                </p>
                                <small class="text-muted">JPG, PNG, GIF, WEBP — max 5MB</small>
                            </div>
                            <div id="imagePreview" style="display:none" class="mt-2">
                                <img id="previewImg" src="" alt="Preview" style="max-height:180px;border-radius:8px">
                                <p class="text-muted small mt-1" id="fileName"></p>
                            </div>
                        </div>
                        @error('do_image')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('fuel.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-solar px-5">
                        <i class="bi bi-save me-2"></i>Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function calcRate() {
    const L = parseFloat(document.getElementById('liters').value)||0;
    const RM = parseFloat(document.getElementById('amount_rm').value)||0;
    document.getElementById('rateValue').textContent = (L>0&&RM>0) ? 'RM '+(RM/L).toFixed(4) : 'RM —';
}
document.getElementById('liters').addEventListener('input',calcRate);
document.getElementById('amount_rm').addEventListener('input',calcRate);

const input = document.getElementById('do_image');
input.addEventListener('change', () => {
    const file = input.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('imagePreview').style.display = 'block';
        document.getElementById('dropPrompt').style.display = 'none';
    };
    reader.readAsDataURL(file);
});
</script>
@endsection
