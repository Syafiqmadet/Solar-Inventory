@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', $zone->name)
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-geo-alt me-2"></i>{{ $zone->name }}</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">{{ $zone->description }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('zones.export', $zone) }}" class="btn btn-outline-light fw-semibold">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            </a>
            @if($canWrite)
            <a href="{{ route('zones.stock.form', $zone) }}" class="btn btn-light"><i class="bi bi-arrow-left-right me-2"></i>Stock In/Out</a>
            <a href="{{ route('zones.edit', $zone) }}" class="btn btn-outline-light">Edit</a>
            @endif
        </div>
    </div>
</div>
<div class="content-area">
    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-4" id="zoneTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tabTransactions">
                <i class="bi bi-arrow-left-right me-1"></i>Transactions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tabSubcon">
                <i class="bi bi-people me-1"></i>Subcon
                <span class="badge bg-secondary ms-1">{{ $subcons->count() }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
    {{-- TRANSACTIONS TAB --}}
    <div class="tab-pane fade show active" id="tabTransactions">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-success mb-0">{{ $zone->transactions->where('type','in')->sum('quantity') }}</h4>
                <small class="text-muted">Total Stock In</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-danger mb-0">{{ $zone->transactions->where('type','out')->sum('quantity') }}</h4>
                <small class="text-muted">Total Stock Out</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-primary mb-0">{{ $zone->transactions->count() }}</h4>
                <small class="text-muted">Transactions</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-info mb-0">{{ $zone->transactions->pluck('item_id')->unique()->count() }}</h4>
                <small class="text-muted">Unique Items</small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3"><h6 class="mb-0">Transaction History for {{ $zone->name }}</h6></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr>
                    <th class="px-4">Date</th>
                    <th>Item</th>
                    <th>Part No.</th>
                    <th>Type</th>
                    <th class="text-center">Quantity</th>
                    <th>Notes</th>
                </tr></thead>
                <tbody>
                    @forelse($zone->transactions->sortByDesc('created_at') as $tx)
                    <tr>
                        <td class="px-4 small text-muted">{{ $tx->created_at->format('d M Y H:i') }}</td>
                        <td class="fw-semibold">{{ $tx->item->name ?? '-' }}</td>
                        <td><code class="small">{{ $tx->item->part_number ?? '' }}</code></td>
                        <td>
                            @if($tx->type == 'in')
                                <span class="badge bg-success"><i class="bi bi-arrow-down me-1"></i>IN</span>
                            @else
                                <span class="badge bg-danger"><i class="bi bi-arrow-up me-1"></i>OUT</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold">{{ $tx->quantity }}</td>
                        <td class="text-muted small">{{ $tx->notes ?? '' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No transactions for this zone yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>{{-- /tab-pane transactions --}}

    {{-- SUBCON TAB --}}
    <div class="tab-pane fade" id="tabSubcon">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0"><i class="bi bi-people me-2"></i>Subcontractors assigned to this zone</h6>
            <a href="{{ route('subcon.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-box-arrow-up-right me-1"></i>Manage All Subcons
            </a>
        </div>

        @forelse($subcons as $sc)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:#f8f9fa">
                <div>
                    <span class="fw-semibold">{{ $sc->name }}</span>
                    {!! $sc->status_badge !!}
                    @if($sc->supervisor_name)
                    <span class="text-muted small ms-3"><i class="bi bi-person-badge me-1"></i>{{ $sc->supervisor_name }}
                        @if($sc->supervisor_contact) · {{ $sc->supervisor_contact }}@endif
                    </span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('subcon.mif', $sc) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-right me-1"></i>MIF ({{ $sc->mifs->count() }})
                    </a>
                    <a href="{{ route('subcon.mrf', $sc) }}" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-box-arrow-in-left me-1"></i>MRF ({{ $sc->mrfs->count() }})
                    </a>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="row g-3 text-center">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Period</small>
                        <span class="small">{{ $sc->start_date?->format('d M Y') ?? '—' }} → {{ $sc->end_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Total Issued (MIF)</small>
                        <span class="fw-bold text-danger">{{ $sc->mifs->sum(fn($m) => $m->items->sum('quantity')) }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Returned Good</small>
                        <span class="fw-bold text-success">{{ $sc->mrfs->sum(fn($m) => $m->items->where('condition','good')->sum('quantity')) }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Returned Damaged</small>
                        <span class="fw-bold text-warning">{{ $sc->mrfs->sum(fn($m) => $m->items->where('condition','damaged')->sum('quantity')) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-people display-6 d-block mb-2 opacity-25"></i>
            No subcontractors assigned to this zone yet.
            <div class="mt-2">
                <a href="{{ route('subcon.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Subcon
                </a>
            </div>
        </div>
        @endforelse
    </div>{{-- /tab-pane subcon --}}

    </div>{{-- /tab-content --}}
</div>
@endsection
