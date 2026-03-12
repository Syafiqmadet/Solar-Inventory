@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'Fuel Record Detail')
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>{{ $fuel->fuel_type === 'petrol' ? '⛽ Petrol' : '🔵 Diesel' }} Record</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">{{ $fuel->date->format('d F Y') }}{{ $fuel->do_number ? ' | DO: '.$fuel->do_number : '' }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($canWrite)
            <a href="{{ route('fuel.edit', $fuel) }}" class="btn btn-light"><i class="bi bi-pencil me-1"></i>Edit</a>
            @endif
            <a href="{{ route('fuel.index') }}" class="btn btn-outline-light">Back</a>
        </div>
    </div>
</div>
<div class="content-area">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body p-4">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;background:{{ $fuel->fuel_type==='petrol'?'#e8f8ee':'#e8f0ff' }};border:3px solid {{ $fuel->fuel_type==='petrol'?'#28a745':'#0d6efd' }}">
                        <span style="font-size:2.2rem">{{ $fuel->fuel_type==='petrol'?'⛽':'🔵' }}</span>
                    </div>
                    <span class="badge fs-6 {{ $fuel->fuel_type==='petrol'?'bg-success':'bg-primary' }} mb-3">{{ ucfirst($fuel->fuel_type) }}</span>
                    <div class="row g-2 mt-1">
                        <div class="col-6"><div class="p-3 rounded" style="background:#e8f8ee"><div class="fw-bold fs-4 text-success">{{ number_format($fuel->liters, 2) }}</div><small class="text-muted">Liters</small></div></div>
                        <div class="col-6"><div class="p-3 rounded" style="background:#fff3e0"><div class="fw-bold fs-5 text-warning">RM {{ number_format($fuel->amount_rm, 2) }}</div><small class="text-muted">Amount</small></div></div>
                        <div class="col-12"><div class="p-2 rounded" style="background:#e8f0ff"><div class="fw-bold text-primary">RM {{ number_format($fuel->price_per_liter, 4) }}</div><small class="text-muted">Per Liter</small></div></div>
                    </div>
                </div>
            </div>
            @if($fuel->do_image)
            <div class="card mt-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="bi bi-image me-2 text-info"></i>DO Document</h6></div>
                <div class="card-body text-center p-3">
                    @php $ext = strtolower(pathinfo($fuel->do_image, PATHINFO_EXTENSION)); @endphp
                    @if(in_array($ext, ['jpg','jpeg','png','webp']))
                        <a href="{{ asset('storage/'.$fuel->do_image) }}" target="_blank">
                            <img src="{{ asset('storage/'.$fuel->do_image) }}" style="max-width:100%;max-height:240px;border-radius:8px;border:2px solid #dee2e6" alt="DO" class="shadow-sm">
                        </a>
                        <p class="mt-2 mb-0 text-muted small">Click to open full size</p>
                    @else
                        <a href="{{ asset('storage/'.$fuel->do_image) }}" target="_blank" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf me-2"></i>Open PDF</a>
                    @endif
                </div>
            </div>
            @endif
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Record Details</h6></div>
                <div class="card-body p-0">
                    <table class="table table-borderless mb-0">
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted" width="180">Fuel Type</th><td class="py-3"><span class="badge fs-6 {{ $fuel->fuel_type==='petrol'?'bg-success':'bg-primary' }}">{{ ucfirst($fuel->fuel_type) }}</span></td></tr>
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted">Date</th><td class="py-3 fw-semibold">{{ $fuel->date->format('d F Y') }}</td></tr>
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted">Liters</th><td class="py-3 fw-bold text-success fs-5">{{ number_format($fuel->liters, 2) }} L</td></tr>
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted">Amount (RM)</th><td class="py-3 fw-bold text-warning fs-5">RM {{ number_format($fuel->amount_rm, 2) }}</td></tr>
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted">Price / Liter</th><td class="py-3 text-primary fw-semibold">RM {{ number_format($fuel->price_per_liter, 4) }}</td></tr>
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted">DO Number</th><td class="py-3">{{ $fuel->do_number ? '<code class="fs-6">'.$fuel->do_number.'</code>' : '<span class="text-muted">—</span>' }}</td></tr>
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted">Supplier</th><td class="py-3">{{ $fuel->supplier ?: '—' }}</td></tr>
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted">Vehicle No.</th><td class="py-3">{{ $fuel->vehicle_no ?: '—' }}</td></tr>
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted">DO Document</th><td class="py-3">@if($fuel->do_image)<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Uploaded</span>@else<span class="badge bg-secondary">No file</span>@endif</td></tr>
                        <tr class="border-bottom"><th class="px-4 py-3 text-muted">Notes</th><td class="py-3 text-muted">{{ $fuel->notes ?: '—' }}</td></tr>
                        <tr><th class="px-4 py-3 text-muted">Created</th><td class="py-3 text-muted small">{{ $fuel->created_at->format('d M Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-body d-flex gap-2">
                    @if($canWrite)
                    <a href="{{ route('fuel.edit', $fuel) }}" class="btn btn-solar"><i class="bi bi-pencil me-2"></i>Edit</a>
                    <form action="{{ route('fuel.destroy', $fuel) }}" method="POST" onsubmit="return confirm('Delete this fuel record?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                    </form>
                    @endif
                    <a href="{{ route('fuel.index') }}" class="btn btn-outline-secondary ms-auto"><i class="bi bi-arrow-left me-2"></i>Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
