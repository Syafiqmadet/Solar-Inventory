@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'Isolated Items')
@section('content')

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-shield-exclamation me-2"></i>Isolated Items</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Defective or damaged items quarantined from inventory</p>
        </div>
        @if($canWrite)
        <a href="{{ route('isolated.create') }}" class="btn btn-success fw-semibold">
            <i class="bi bi-plus-circle me-2"></i>Isolate Item
        </a>
        @endif
    </div>
</div>

<div class="content-area">

    {{-- Stats cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card text-center p-3 h-100">
                <div class="fs-2 fw-bold text-dark">{{ $stats['total'] }}</div>
                <div class="small text-muted">Total Records</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center p-3 h-100" style="border-left:4px solid #fd7e14">
                <div class="fs-2 fw-bold text-warning">{{ $stats['defect'] }}</div>
                <div class="small text-muted">⚠️ Defect</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center p-3 h-100" style="border-left:4px solid #dc3545">
                <div class="fs-2 fw-bold text-danger">{{ $stats['damaged'] }}</div>
                <div class="small text-muted">💥 Damaged</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center p-3 h-100" style="border-left:4px solid #fd7e14">
                <div class="fs-2 fw-bold" style="color:#fd7e14">{{ $stats['isolated'] }}</div>
                <div class="small text-muted">🔒 Isolated</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center p-3 h-100" style="border-left:4px solid #28a745">
                <div class="fs-2 fw-bold text-success">{{ $stats['repaired'] }}</div>
                <div class="small text-muted">✅ Repaired</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center p-3 h-100" style="border-left:4px solid #dc3545">
                <div class="fs-2 fw-bold text-danger">{{ $stats['qty'] }}</div>
                <div class="small text-muted">Total Units</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">🔍 Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Name, part number, reason…" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="defect"  {{ request('type')=='defect'  ? 'selected':'' }}>⚠️ Defect</option>
                        <option value="damaged" {{ request('type')=='damaged' ? 'selected':'' }}>💥 Damaged</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="isolated" {{ request('status')=='isolated' ? 'selected':'' }}>🔒 Isolated</option>
                        <option value="scrapped" {{ request('status')=='scrapped' ? 'selected':'' }}>🗑️ Scrapped</option>
                        <option value="repaired" {{ request('status')=='repaired' ? 'selected':'' }}>✅ Repaired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-solar btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('isolated.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-shield-exclamation me-2"></i>Isolated Items</h6>
            <span class="badge bg-secondary">{{ $isolated->total() }} records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">Date</th>
                            <th>Name</th>
                            <th>Part Number</th>
                            <th class="text-center">Qty</th>
                            <th>Type</th>
                            <th>MRF No.</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($isolated as $record)
                        <tr>
                            <td class="px-4 small text-muted">
                                {{ $record->isolated_date->format('d M Y') }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $record->name }}</div>
                                @if($record->item)
                                    <div class="small text-muted">
                                        <i class="bi bi-link-45deg"></i> Linked to inventory
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($record->part_number)
                                    <code class="small">{{ $record->part_number }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center fw-bold">{{ $record->quantity }}</td>
                            <td>
                                @if($record->type === 'defect')
                                    <span class="badge" style="background:#fff3cd;color:#664d03;border:1px solid #ffc107">
                                        ⚠️ Defect
                                    </span>
                                @else
                                    <span class="badge" style="background:#f8d7da;color:#842029;border:1px solid #dc3545">
                                        💥 Damaged
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted small" style="max-width:200px">
                                {{ Str::limit($record->reason, 50) ?: '—' }}
                                @if($record->proof_images && is_array($record->proof_images) && count($record->proof_images) > 0)
                                <div class="d-flex gap-1 mt-1 flex-wrap">
                                    @foreach($record->proof_images as $img)
                                    @if($img)
                                    <a href="{{ $img }}" target="_blank">
                                        <img src="{{ $img }}" style="width:32px;height:32px;object-fit:cover;border-radius:4px;border:1px solid #dee2e6" title="Proof image">
                                    </a>
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                            </td>
                            <td class="small">
                                @php
                                    preg_match('/MRF (\S+)/', $record->reason ?? '', $mrfMatch);
                                    $mrfNo = $mrfMatch[1] ?? null;
                                @endphp
                                @if($mrfNo)
                                    <span class="badge bg-info text-dark">{{ $mrfNo }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($record->status === 'isolated')
                                    <span class="badge bg-warning text-dark">🔒 Isolated</span>
                                @elseif($record->status === 'repaired')
                                    <span class="badge bg-success">✅ Repaired</span>
                                @else
                                    <span class="badge bg-danger">🗑️ Scrapped</span>
                                @endif
                            </td>
                            <td class="px-3">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('isolated.show', $record) }}"
                                       class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($canWrite)
                                    <a href="{{ route('isolated.edit', $record) }}"
                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('isolated.destroy', $record) }}" method="POST"
                                          onsubmit="return confirm('Delete this record?')">
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
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-shield-check" style="font-size:2.5rem;color:#ccc"></i>
                                <p class="mt-2 mb-0">No isolated items found.
                                    @if($canWrite)
                                        <a href="{{ route('isolated.create') }}">Isolate an item</a>
                                    @endif
                                </p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($isolated->hasPages())
        <div class="card-footer bg-white">{{ $isolated->links() }}</div>
        @endif
    </div>
</div>
@endsection
