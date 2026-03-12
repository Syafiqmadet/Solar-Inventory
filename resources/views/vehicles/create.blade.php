@extends('layouts.app')
@section('title', 'Add Vehicle')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-truck me-2"></i>Add Vehicle</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Register a new vehicle for diesel tracking</p>
</div>

<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-truck me-2"></i>Vehicle Details</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('vehicles.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vehicle Plate No. <span class="text-danger">*</span></label>
                                <input type="text" name="vehicle_no" class="form-control text-uppercase @error('vehicle_no') is-invalid @enderror"
                                       value="{{ old('vehicle_no') }}" placeholder="e.g. WXX 1234" required>
                                @error('vehicle_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vehicle Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="e.g. Site Lorry 1" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vehicle Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">-- Select Type --</option>
                                    <optgroup label="🚛 Heavy">
                                        <option value="Lorry"     {{ old('type')=='Lorry'?'selected':'' }}>🚛 Lorry / Truck</option>
                                        <option value="Crane"     {{ old('type')=='Crane'?'selected':'' }}>🏗️ Crane</option>
                                        <option value="Excavator" {{ old('type')=='Excavator'?'selected':'' }}>🚜 Excavator / JCB</option>
                                        <option value="Forklift"  {{ old('type')=='Forklift'?'selected':'' }}>🏭 Forklift</option>
                                    </optgroup>
                                    <optgroup label="🚐 Medium">
                                        <option value="Pickup"    {{ old('type')=='Pickup'?'selected':'' }}>🚐 Pickup Truck</option>
                                        <option value="Van"       {{ old('type')=='Van'?'selected':'' }}>🚐 Van</option>
                                    </optgroup>
                                    <optgroup label="⚡ Equipment">
                                        <option value="Generator" {{ old('type')=='Generator'?'selected':'' }}>⚡ Generator</option>
                                        <option value="Compressor"{{ old('type')=='Compressor'?'selected':'' }}>🔧 Compressor</option>
                                    </optgroup>
                                    <option value="Other"         {{ old('type')=='Other'?'selected':'' }}>🚗 Other</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vehicle Color</label>
                                <div class="input-group">
                                    <input type="color" name="color" id="vColor"
                                           class="form-control form-control-color"
                                           value="{{ old('color', '#0d6efd') }}" style="max-width:60px">
                                    <input type="text" id="vColorText" class="form-control" value="{{ old('color', '#0d6efd') }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Brand</label>
                                <input type="text" name="brand" class="form-control"
                                       value="{{ old('brand') }}" placeholder="e.g. Isuzu, Hino, Volvo">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Model</label>
                                <input type="text" name="model" class="form-control"
                                       value="{{ old('model') }}" placeholder="e.g. NPR, 300 Series">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Optional notes...">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                                <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-solar px-4">
                                    <i class="bi bi-save me-2"></i>Save Vehicle
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
const vColor = document.getElementById('vColor');
const vText  = document.getElementById('vColorText');
vColor.addEventListener('input', () => vText.value = vColor.value);
document.querySelector('[name=vehicle_no]').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>
@endsection
