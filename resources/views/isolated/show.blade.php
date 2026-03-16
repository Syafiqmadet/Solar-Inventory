@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', $isolated->name)
@section('content')

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                @if($isolated->type === 'defect')
                    <span class="me-2">⚠️</span>
                @else
                    <span class="me-2">💥</span>
                @endif
                {{ $isolated->name }}
            </h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">
                Isolated on {{ $isolated->isolated_date->format('d M Y') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($canWrite)
            <a href="{{ route('isolated.edit', $isolated) }}" class="btn btn-light">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @endif
            <a href="{{ route('isolated.index') }}" class="btn btn-outline-light">Back</a>
        </div>
    </div>
</div>

<div class="content-area">
    <div class="row g-4">

        {{-- Left info card --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body p-4">

                    {{-- Type badge --}}
                    <div class="text-center mb-3">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2"
                             style="width:70px;height:70px;background:{{ $isolated->type==='defect' ? '#fff3cd' : '#f8d7da' }};
                                    border:3px solid {{ $isolated->type==='defect' ? '#ffc107' : '#dc3545' }}">
                            <span style="font-size:2rem">{{ $isolated->type==='defect' ? '⚠️' : '💥' }}</span>
                        </div>
                        <div class="fw-bold fs-5">{{ ucfirst($isolated->type) }}</div>
                        @if($isolated->part_number)
                            <code class="small">{{ $isolated->part_number }}</code>
                        @endif
                    </div>

                    <hr>

                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="p-2 rounded" style="background:#f0f0f0">
                                <div class="small text-muted">Quantity</div>
                                <div class="fw-bold fs-4">{{ $isolated->quantity }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded" style="background:#f0f0f0">
                                <div class="small text-muted">Date</div>
                                <div class="fw-bold small">{{ $isolated->isolated_date->format('d M Y') }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            @if($isolated->status === 'isolated')
                                <span class="badge bg-warning text-dark fs-6 px-3 py-2">🔒 Isolated</span>
                            @elseif($isolated->status === 'repaired')
                                <span class="badge bg-success fs-6 px-3 py-2">✅ Repaired</span>
                            @else
                                <span class="badge bg-danger fs-6 px-3 py-2">🗑️ Scrapped</span>
                            @endif
                        </div>
                    </div>

                    @if($isolated->item)
                    <hr>
                    <div class="small">
                        <div class="text-muted mb-1">Linked Inventory Item</div>
                        <div class="fw-semibold">{{ $isolated->item->name }}</div>
                        <code class="small">{{ $isolated->item->part_number }}</code>
                        <div class="mt-1">Current stock: <strong>{{ $isolated->item->current_stock }}</strong></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right detail card --}}
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-card-text me-2"></i>Reason &amp; Notes</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="text-muted small fw-semibold text-uppercase mb-2">Reason / Description</div>
                        @if($isolated->reason)
                            <div class="p-3 rounded" style="background:#f8f9fa;border-left:4px solid {{ $isolated->type==='defect' ? '#ffc107' : '#dc3545' }}">
                                {{ $isolated->reason }}
                            </div>
                        @else
                            <span class="text-muted">No reason provided.</span>
                        @endif
                    </div>

                    @if($isolated->notes)
                    <div class="mb-4">
                        <div class="text-muted small fw-semibold text-uppercase mb-2">Notes</div>
                        <div class="p-3 rounded bg-light">{{ $isolated->notes }}</div>
                    </div>
                    @endif

                    @if($isolated->proof_images && count($isolated->proof_images) > 0)
                    <div class="mb-4">
                        <div class="text-muted small fw-semibold text-uppercase mb-2">📷 Proof Images ({{ count($isolated->proof_images) }})</div>
                        <div class="d-flex gap-3 flex-wrap">
                            @foreach($isolated->proof_images as $idx => $img)
                            @if($img)
                            <div class="text-center">
                                <a href="{{ $img }}" target="_blank">
                                    <img src="{{ $img }}"
                                        style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:2px solid #dee2e6;cursor:pointer"
                                        class="shadow-sm"
                                        alt="Proof {{ $idx + 1 }}">
                                </a>
                                <div class="small text-muted mt-1">Photo {{ $idx + 1 }}</div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <hr>

                    <div class="row g-2 text-muted small">
                        <div class="col-6">Created: {{ $isolated->created_at->format('d M Y H:i') }}</div>
                        <div class="col-6">Updated: {{ $isolated->updated_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    @if($canWrite)
                    <a href="{{ route('isolated.edit', $isolated) }}" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                    <form action="{{ route('isolated.destroy', $isolated) }}" method="POST"
                          onsubmit="return confirm('Delete this isolated item record?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
