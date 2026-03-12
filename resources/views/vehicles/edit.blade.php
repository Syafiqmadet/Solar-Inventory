@extends('layouts.app')
@section('title', 'Edit Vehicle')
@section('content')
<div class="page-header">
    <h1><i class="bi bi-pencil me-2"></i>Edit Vehicle: {{ $vehicle->name }}</h1>
</div>
<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body p-4">
                    <form action="{{ route('vehicles.update', $vehicle) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Plate No. *</label>
                                <input type="text" name="vehicle_no" class="form-control text-uppercase"
                                       value="{{ old('vehicle_no', $vehicle->vehicle_no) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Vehicle Name *</label>
                                <input type="text" name="name" class="form-control"
                                       value="{{ old('name', $vehicle->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type *</label>
                                <select name="type" class="form-select" required>
                                    @foreach(['Lorry','Crane','Excavator','Forklift','Pickup','Van','Generator','Compressor','Other'] as $t)
                                    <option value="{{ $t }}" {{ old('type',$vehicle->type)==$t?'selected':'' }}>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Color</label>
                                <div class="input-group">
                                    <input type="color" name="color" id="vColor" class="form-control form-control-color"
                                           value="{{ old('color', $vehicle->color ?? '#0d6efd') }}" style="max-width:60px">
                                    <input type="text" id="vColorText" class="form-control"
                                           value="{{ old('color', $vehicle->color ?? '#0d6efd') }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Brand</label>
                                <input type="text" name="brand" class="form-control"
                                       value="{{ old('brand', $vehicle->brand) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Model</label>
                                <input type="text" name="model" class="form-control"
                                       value="{{ old('model', $vehicle->model) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $vehicle->notes) }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                           {{ old('is_active', $vehicle->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Active Vehicle</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                                <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-solar px-4"><i class="bi bi-save me-2"></i>Update</button>
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
const vColor=document.getElementById('vColor'),vText=document.getElementById('vColorText');
vColor.addEventListener('input',()=>vText.value=vColor.value);
</script>
@endsection
