@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', $vehicle->name)

@section('styles')
<style>
.row-petrol  { border-left: 4px solid #28a745; }
.row-diesel  { border-left: 4px solid #0d6efd; }
.row-low     { border-left: 4px solid #ffc107; }
.row-empty   { border-left: 4px solid #dc3545; }
.fuel-pill   { font-size:0.75rem; font-weight:700; padding:3px 10px; border-radius:20px; }
.pill-petrol { background:#e8f8ee; color:#28a745; }
.pill-diesel { background:#e8f0ff; color:#0d6efd; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>{{ $vehicle->type_icon }} {{ $vehicle->name }}</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">
                <code style="color:rgba(255,255,255,0.9)">{{ $vehicle->vehicle_no }}</code>
                &nbsp;·&nbsp; {{ $vehicle->type }}
                @if($vehicle->brand) &nbsp;·&nbsp; {{ $vehicle->brand }} {{ $vehicle->model }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($canWrite)
            <a href="{{ route('vehicles.usage.form', $vehicle) }}" class="btn btn-light fw-semibold">
                <i class="bi bi-fuel-pump me-2"></i>Log Fuel
            </a>
            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-outline-light">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @endif
            <a href="{{ route('vehicles.index') }}" class="btn btn-outline-light">Back</a>
        </div>
    </div>
</div>

<div class="content-area">

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3" style="border-left:4px solid #28a745">
                <div class="fw-bold fs-3 text-success">{{ number_format($vehiclePetrolUsed, 1) }}</div>
                <small class="text-muted">⛽ Petrol Used (L)</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3" style="border-left:4px solid #0d6efd">
                <div class="fw-bold fs-3 text-primary">{{ number_format($vehicleDieselUsed, 1) }}</div>
                <small class="text-muted">🛢️ Diesel Used (L)</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="fw-bold fs-3 text-info">{{ $usages->total() }}</div>
                <small class="text-muted">Total Records</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <div class="fw-bold fs-3 {{ $diesel['balance'] < 50 ? 'text-warning' : 'text-success' }}">
                    {{ number_format($diesel['balance'], 1) }}
                </div>
                <small class="text-muted">🛢️ Diesel Balance (L)</small>
            </div>
        </div>
    </div>

    {{-- Usage History --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Fuel Usage History — {{ $vehicle->name }}</h6>
            @if($canWrite)
            <a href="{{ route('vehicles.usage.form', $vehicle) }}" class="btn btn-sm btn-solar">
                <i class="bi bi-plus me-1"></i>Log Fuel
            </a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-4">Date</th>
                            <th>Fuel</th>
                            <th class="text-end">Liters Used</th>
                            <th class="text-end">Balance Before</th>
                            <th class="text-end">Balance After</th>
                            <th>Driver</th>
                            <th>Odometer</th>
                            <th>Purpose</th>
                            <th class="px-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usages as $u)
                        @php
                            $isPetrol   = $u->fuel_type === 'petrol';
                            $afterClass = $u->balance_after <= 0  ? 'text-danger fw-bold'
                                        : ($u->balance_after < 50 ? 'text-warning fw-bold'
                                                                   : 'text-success fw-bold');
                            $rowClass   = $u->balance_after <= 0  ? 'row-empty'
                                        : ($u->balance_after < 50 ? 'row-low'
                                        : ($isPetrol ? 'row-petrol' : 'row-diesel'));
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-4">
                                <span class="fw-semibold">{{ $u->date->format('d M Y') }}</span><br>
                                <small class="text-muted">{{ $u->date->format('D') }}</small>
                            </td>
                            <td>
                                <span class="fuel-pill {{ $isPetrol ? 'pill-petrol' : 'pill-diesel' }}">
                                    {{ $isPetrol ? '⛽ PETROL' : '🛢️ DIESEL' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-danger fs-6">{{ number_format($u->liters_used,2) }}</span>
                                <small class="text-muted"> L</small>
                            </td>
                            <td class="text-end">
                                <span class="{{ $isPetrol ? 'text-success' : 'text-primary' }}">{{ number_format($u->balance_before,2) }}</span>
                                <small class="text-muted"> L</small>
                            </td>
                            <td class="text-end">
                                <span class="{{ $afterClass }}">{{ number_format($u->balance_after,2) }}</span>
                                <small class="text-muted"> L</small>
                                @if($u->balance_after <= 0)
                                    <br><small class="text-danger">⛔ Empty</small>
                                @elseif($u->balance_after < 50)
                                    <br><small class="text-warning">⚠ Low</small>
                                @endif
                            </td>
                            <td class="small">{{ $u->driver_name ?? '—' }}</td>
                            <td class="small text-muted">{{ $u->odometer_km ? number_format($u->odometer_km).' km' : '—' }}</td>
                            <td class="small text-muted" style="max-width:160px">{{ Str::limit($u->purpose ?? '—', 35) }}</td>
                            <td class="px-4">
                                @if($canWrite)
                                <form action="{{ route('vehicles.usage.destroy', [$vehicle, $u]) }}" method="POST"
                                      onsubmit="return confirm('Delete this usage record? Balances will be recalculated.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <span style="font-size:3rem">⛽</span>
                                <p class="mt-2">No fuel usage recorded yet.</p>
                                @if($canWrite)
                                <a href="{{ route('vehicles.usage.form', $vehicle) }}" class="btn btn-solar btn-sm">
                                    <i class="bi bi-plus me-1"></i>Log First Usage
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($usages->count() > 0)
                    <tfoot>
                        <tr style="background:#f8f9fa">
                            <td colspan="2" class="px-4 fw-bold">Page Total</td>
                            <td class="text-end fw-bold text-danger">
                                {{ number_format($usages->sum('liters_used'),2) }} L
                            </td>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @if(method_exists($usages,'links'))
        <div class="card-footer bg-white">{{ $usages->links() }}</div>
        @endif
    </div>
</div>
@endsection
