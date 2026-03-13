@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'Fuel Records')

@section('styles')
<style>
.fuel-petrol { background: linear-gradient(135deg, #28a745, #20c997); }
.fuel-diesel  { background: linear-gradient(135deg, #0d6efd, #0dcaf0); }
.badge-petrol { background: #28a745; }
.badge-diesel { background: #0d6efd; }
.do-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 2px solid #dee2e6; cursor: pointer; transition: transform .2s; }
.do-thumb:hover { transform: scale(1.1); }
.fuel-type-pill { font-size: 0.78rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; letter-spacing: 0.5px; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-fuel-pump me-2"></i>Fuel Records</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Petrol &amp; Diesel purchase tracking</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="{{ route('fuel.export') }}" class="btn btn-success fw-semibold me-2">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            
        
        @if($canWrite)
        <a href="{{ route('fuel.create') }}" class="btn btn-success fw-semibold me-2">
            <i class="bi bi-plus-circle me-2"></i>Add Fuel Record
        </a>
        @endif
    </div>
</div>

<div class="content-area">

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 text-white fuel-petrol">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small text-uppercase fw-semibold">Petrol Total</p>
                            <h3 class="mb-0 fw-bold">{{ number_format($totalPetrolLiters, 1) }}L</h3>
                            <small class="opacity-75">RM {{ number_format($totalPetrolRM, 2) }}</small>
                        </div>
                        <span style="font-size:2rem;opacity:0.25">⛽</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-white fuel-diesel">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small text-uppercase fw-semibold">Diesel Total</p>
                            <h3 class="mb-0 fw-bold">{{ number_format($totalDieselLiters, 1) }}L</h3>
                            <small class="opacity-75">RM {{ number_format($totalDieselRM, 2) }}</small>
                        </div>
                        <span style="font-size:2rem;opacity:0.25">🚛</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-white" style="background:linear-gradient(135deg,#f093fb,#f5576c)">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small text-uppercase fw-semibold">Total Spend</p>
                            <h3 class="mb-0 fw-bold">RM {{ number_format($totalSpend, 2) }}</h3>
                            <small class="opacity-75">All fuel types</small>
                        </div>
                        <span style="font-size:2rem;opacity:0.25">💰</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-white" style="background:linear-gradient(135deg,#667eea,#764ba2)">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small text-uppercase fw-semibold">Total Records</p>
                            <h3 class="mb-0 fw-bold">{{ $totalRecords }}</h3>
                            <small class="opacity-75">Purchase entries</small>
                        </div>
                        <span style="font-size:2rem;opacity:0.25">📋</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Fuel Type</label>
                    <select name="fuel_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="petrol" {{ request('fuel_type')=='petrol'?'selected':'' }}>⛽ Petrol</option>
                        <option value="diesel" {{ request('fuel_type')=='diesel'?'selected':'' }}>🚛 Diesel</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="DO No., supplier, vehicle..." value="{{ request('search') }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-solar btn-sm w-100"><i class="bi bi-search"></i></button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('fuel.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Records Table --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0"><i class="bi bi-table me-2 text-warning"></i>Fuel Purchase Records</h6>
            <span class="badge bg-secondary">{{ $records->total() }} records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="px-4">Date</th>
                            <th>Type</th>
                            <th class="text-end">Liters (L)</th>
                            <th class="text-end">Amount (RM)</th>
                            <th class="text-end">RM/L</th>
                            <th>DO Number</th>
                            <th class="text-center">DO Image</th>
                            <th>Supplier</th>
                            <th>Vehicle</th>
                            <th class="px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                        <tr>
                            <td class="px-4">
                                <span class="fw-semibold">{{ $record->date->format('d M Y') }}</span><br>
                                <small class="text-muted">{{ $record->date->format('D') }}</small>
                            </td>
                            <td>
                                @if($record->fuel_type === 'petrol')
                                    <span class="fuel-type-pill badge-petrol text-white">⛽ PETROL</span>
                                @else
                                    <span class="fuel-type-pill badge-diesel text-white">🚛 DIESEL</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">{{ number_format($record->liters, 2) }}</td>
                            <td class="text-end fw-bold text-success">RM {{ number_format($record->amount_rm, 2) }}</td>
                            <td class="text-end text-muted small">{{ number_format($record->price_per_liter, 4) }}</td>
                            <td>
                                @if($record->do_number)
                                    <code class="small text-dark">{{ $record->do_number }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($record->do_image)
                                    <img src="{{ $record->do_image }}"
                                         class="do-thumb"
                                         alt="DO"
                                         data-bs-toggle="modal"
                                         data-bs-target="#imgModal{{ $record->id }}"
                                         title="Click to enlarge">
                                    {{-- Image Modal --}}
                                    <div class="modal fade" id="imgModal{{ $record->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">DO Image — {{ $record->do_number ?? 'Record #'.$record->id }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center p-2">
                                                    <img src="{{ $record->do_image }}"
                                                         class="img-fluid rounded" style="max-height:80vh">
                                                </div>
                                                <div class="modal-footer">
                                                    <a href="{{ $record->do_image }}"
                                                       download class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-download me-1"></i>Download
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">No image</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $record->supplier ?? '—' }}</td>
                            <td>
                                @if($record->vehicle_no)
                                    <span class="badge bg-light text-dark border">{{ $record->vehicle_no }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('fuel.show', $record) }}"
                                       class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                    @if($canWrite)
                                    @if($canWrite)
                                    <a href="{{ route('fuel.edit', $record) }}"
                                       class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @endif
                                    <form action="{{ route('fuel.destroy', $record) }}" method="POST"
                                          onsubmit="return confirm('Delete this fuel record?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <span style="font-size:3rem">⛽</span>
                                <p class="mt-2 mb-0">No fuel records yet.</p>
                                @if($canWrite)
                                <a href="{{ route('fuel.create') }}" class="btn btn-solar btn-sm mt-3">
                                    <i class="bi bi-plus me-1"></i>Add First Record
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($records->count() > 0)
                    <tfoot>
                        <tr style="background:#f8f9fa">
                            <td class="px-4 fw-bold" colspan="2">Page Total</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($records->sum('liters'), 2) }} L</td>
                            <td class="text-end fw-bold text-success">RM {{ number_format($records->sum('amount_rm'), 2) }}</td>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @if(method_exists($records,'links'))
        <div class="card-footer bg-white">{{ $records->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
