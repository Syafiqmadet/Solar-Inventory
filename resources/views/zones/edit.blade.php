@extends('layouts.app')
@section('title', 'Edit Zone')
@section('content')
<div class="page-header">
    <h1><i class="bi bi-pencil me-2"></i>Edit Zone: {{ $zone->name }}</h1>
</div>
<div class="content-area">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body p-4">
                    <form action="{{ route('zones.update', $zone) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Zone Name *</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $zone->name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Zone Code</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $zone->code) }}" maxlength="5">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $zone->description) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                @include('partials.color-swatch', ['fieldName'=>'color','fieldId'=>'zone_color','default'=>old('color',$zone->color ?? '#3B82F6'),'label'=>'Zone Color'])
                            </div>
                            <div class="col-12 d-flex gap-2 justify-content-end mt-3">
                                <a href="{{ route('zones.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-solar px-4"><i class="bi bi-save me-2"></i>Update Zone</button>
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
@stack('scripts')
@endsection
