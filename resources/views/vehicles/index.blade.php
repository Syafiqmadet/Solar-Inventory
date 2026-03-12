@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'Vehicles')

@section('styles')
<style>
.vehicle-card { transition: transform .2s, box-shadow .2s; cursor: pointer; }
.vehicle-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.13) !important; }
.type-badge { font-size:0.72rem; font-weight:700; letter-spacing:0.5px; padding:4px 10px; border-radius:20px; }
.bal-bar-wrap { background:#e9ecef; border-radius:20px; height:8px; overflow:hidden; }
.bal-bar { height:100%; border-radius:20px; transition:width .6s; }
.bal-bar.ok       { background:linear-gradient(90deg,#28a745,#20c997); }
.bal-bar.low      { background:linear-gradient(90deg,#ffc107,#fd7e14); }
.bal-bar.critical { background:linear-gradient(90deg,#dc3545,#e83e8c); }
.fuel-tab.active-petrol { border-bottom: 3px solid #28a745; color:#28a745; font-weight:700; }
.fuel-tab.active-diesel { border-bottom: 3px solid #0d6efd; color:#0d6efd; font-weight:700; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-truck me-2"></i>Vehicle Fuel Usage</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Track petrol &amp; diesel usage per vehicle with running balance</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('fuel.export') }}" class="btn btn-success fw-semibold me-2">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            </a>
            @if($canWrite)
            <a href="{{ route('vehicles.create') }}" class="btn btn-success fw-semibold me-2">
                <i class="bi bi-plus-circle me-2"></i>Add Vehicle
            </a>
            @endif
        </div>
    </div>
</div>

<div class="content-area">

    {{-- ⛽ Petrol Stock --}}
    <p class="fw-bold text-muted small text-uppercase mb-2 mt-0">⛽ Petrol Stock</p>
    <div class="row g-3 mb-2">
        <div class="col-md-4">
            <div class="card border-0 text-white" style="background:linear-gradient(135deg,#28a745,#20c997)">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 opacity-75 small text-uppercase fw-semibold">Purchased</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($petrol['purchased'],1) }} L</h3>
                    </div>
                    <span style="font-size:2rem;opacity:0.25">🛢️</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-white" style="background:linear-gradient(135deg,#fd7e14,#e83e8c)">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 opacity-75 small text-uppercase fw-semibold">Used</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($petrol['used'],1) }} L</h3>
                    </div>
                    <span style="font-size:2rem;opacity:0.25">🚗</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @php $pb = $petrol['balance']; @endphp
            <div class="card border-0 text-white"
                 style="background:{{ $pb<=0 ? 'linear-gradient(135deg,#dc3545,#e83e8c)' : ($pb<50 ? 'linear-gradient(135deg,#ffc107,#fd7e14)' : 'linear-gradient(135deg,#28a745,#20c997)') }}">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 opacity-75 small text-uppercase fw-semibold">Balance</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($pb,1) }} L</h3>
                        <small class="opacity-75">{{ $pb<=0 ? '⚠️ Empty!' : ($pb<50 ? '⚠️ Low' : '✅ OK') }}</small>
                    </div>
                    <span style="font-size:2rem;opacity:0.25">⚖️</span>
                </div>
            </div>
        </div>
    </div>
    @if($petrol['purchased'] > 0)
    <div class="card mb-4">
        <div class="card-body py-2 px-4">
            @php $ppct = min(100, max(0, $petrol['purchased']>0 ? ($petrol['balance']/$petrol['purchased'])*100 : 0)); @endphp
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">Petrol Level</small>
                <small class="fw-semibold {{ $ppct<15?'text-danger':($ppct<30?'text-warning':'text-success') }}">{{ number_format($ppct,1) }}% remaining</small>
            </div>
            <div class="bal-bar-wrap"><div class="bal-bar {{ $ppct<15?'critical':($ppct<30?'low':'ok') }}" style="width:{{ $ppct }}%"></div></div>
        </div>
    </div>
    @else
    <div class="mb-4"></div>
    @endif

    {{-- 🛢️ Diesel Stock --}}
    <p class="fw-bold text-muted small text-uppercase mb-2">🛢️ Diesel Stock</p>
    <div class="row g-3 mb-2">
        <div class="col-md-4">
            <div class="card border-0 text-white" style="background:linear-gradient(135deg,#0d6efd,#0dcaf0)">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 opacity-75 small text-uppercase fw-semibold">Purchased</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($diesel['purchased'],1) }} L</h3>
                    </div>
                    <span style="font-size:2rem;opacity:0.25">🛢️</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-white" style="background:linear-gradient(135deg,#fd7e14,#e83e8c)">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 opacity-75 small text-uppercase fw-semibold">Used</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($diesel['used'],1) }} L</h3>
                    </div>
                    <span style="font-size:2rem;opacity:0.25">🚛</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @php $db = $diesel['balance']; @endphp
            <div class="card border-0 text-white"
                 style="background:{{ $db<=0 ? 'linear-gradient(135deg,#dc3545,#e83e8c)' : ($db<50 ? 'linear-gradient(135deg,#ffc107,#fd7e14)' : 'linear-gradient(135deg,#0d6efd,#0dcaf0)') }}">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 opacity-75 small text-uppercase fw-semibold">Balance</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($db,1) }} L</h3>
                        <small class="opacity-75">{{ $db<=0 ? '⚠️ Empty!' : ($db<50 ? '⚠️ Low' : '✅ OK') }}</small>
                    </div>
                    <span style="font-size:2rem;opacity:0.25">⚖️</span>
                </div>
            </div>
        </div>
    </div>
    @if($diesel['purchased'] > 0)
    <div class="card mb-4">
        <div class="card-body py-2 px-4">
            @php $dpct = min(100, max(0, $diesel['purchased']>0 ? ($diesel['balance']/$diesel['purchased'])*100 : 0)); @endphp
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">Diesel Level</small>
                <small class="fw-semibold {{ $dpct<15?'text-danger':($dpct<30?'text-warning':'text-success') }}">{{ number_format($dpct,1) }}% remaining</small>
            </div>
            <div class="bal-bar-wrap"><div class="bal-bar {{ $dpct<15?'critical':($dpct<30?'low':'ok') }}" style="width:{{ $dpct }}%"></div></div>
        </div>
    </div>
    @else
    <div class="mb-4"></div>
    @endif

    {{-- Vehicle Cards --}}
    <div class="row g-4">
        @forelse($vehicles as $vehicle)
        @php
            $vDiesel = (float) $vehicle->dieselUsages()->where('fuel_type','diesel')->sum('liters_used');
            $vPetrol = (float) $vehicle->dieselUsages()->where('fuel_type','petrol')->sum('liters_used');
            $lastDate = $vehicle->lastUsageDate();
        @endphp
        <div class="col-md-6 col-lg-4">
            <div class="card vehicle-card h-100" onclick="window.location='{{ route('vehicles.show', $vehicle) }}'">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px;background:{{ $vehicle->color ?? '#e9ecef' }}25;border:2px solid {{ $vehicle->color ?? '#dee2e6' }};font-size:1.5rem">
                                {{ $vehicle->type_icon }}
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $vehicle->name }}</h6>
                                <code class="text-muted small">{{ $vehicle->vehicle_no }}</code>
                            </div>
                        </div>
                        <span class="type-badge bg-light text-dark border">{{ $vehicle->type }}</span>
                    </div>
                </div>
                <div class="card-body px-4 pt-2 pb-3">
                    @if($vehicle->brand || $vehicle->model)
                    <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>{{ trim($vehicle->brand.' '.$vehicle->model) }}</p>
                    @endif

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 rounded text-center" style="background:#e8f8ee">
                                <div class="fw-bold text-success">{{ number_format($vPetrol,1) }} L</div>
                                <small class="text-muted">⛽ Petrol Used</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded text-center" style="background:#e8f0ff">
                                <div class="fw-bold text-primary">{{ number_format($vDiesel,1) }} L</div>
                                <small class="text-muted">🛢️ Diesel Used</small>
                            </div>
                        </div>
                    </div>

                    @if($lastDate)
                    <p class="text-muted small mb-3"><i class="bi bi-calendar3 me-1"></i>Last use: {{ $lastDate->format('d M Y') }}</p>
                    @endif

                    <div class="d-flex gap-2">
                        @if($canWrite)
                        <a href="{{ route('vehicles.usage.form', $vehicle) }}"
                           class="btn btn-sm flex-fill fw-semibold text-white"
                           style="background:linear-gradient(135deg,#FF6B35,#ff8c42)"
                           onclick="event.stopPropagation()">
                            <i class="bi bi-fuel-pump me-1"></i>Log Fuel
                        </a>
                        @endif
                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-info" onclick="event.stopPropagation()"><i class="bi bi-eye"></i></a>
                        @if($canWrite)
                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-warning" onclick="event.stopPropagation()"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" onclick="event.stopPropagation()" onsubmit="return confirm('Delete {{ $vehicle->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </div>
                </div>
                @if(!$vehicle->is_active)
                <div class="card-footer bg-secondary text-white text-center py-1 small">Inactive</div>
                @endif
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card text-center py-5">
                <span style="font-size:4rem">🚛</span>
                <p class="mt-3 text-muted fs-5">No vehicles yet.</p>
                @if($canWrite)
                <a href="{{ route('vehicles.create') }}" class="btn btn-solar mx-auto" style="width:200px">
                    <i class="bi bi-plus me-2"></i>Add First Vehicle
                </a>
                @endif
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
