@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', $container->container_id)
@section('content')

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-archive me-2"></i>{{ $container->container_id }}</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">{{ $container->description }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($canWrite)
            <a href="{{ route('containers.edit', $container) }}" class="btn btn-light"><i class="bi bi-pencil me-1"></i>Edit</a>
            <form action="{{ route('containers.destroy', $container) }}" method="POST"
                  onsubmit="return confirm('Delete this container? Stock will be reversed automatically.')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-light text-danger border-danger">
                    <i class="bi bi-trash me-1"></i>Delete & Reverse Stock
                </button>
            </form>
            @endif
            <a href="{{ route('containers.index') }}" class="btn btn-outline-light">Back</a>
        </div>
    </div>
</div>

<div class="content-area">
    <div class="row g-4">

        {{-- Info card --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2"
                             style="width:70px;height:70px;background:{{ $container->color_code ?? '#4facfe' }}20;border:3px solid {{ $container->color_code ?? '#4facfe' }}">
                            <i class="bi bi-archive" style="font-size:1.8rem;color:{{ $container->color_code ?? '#4facfe' }}"></i>
                        </div>
                        <code class="fs-6">{{ $container->container_id }}</code>
                        @if($container->batch)
                        <div class="mt-1">
                            <span class="badge bg-info text-dark"><i class="bi bi-collection me-1"></i>{{ $container->batch }}</span>
                        </div>
                        @endif
                    </div>
                    <hr>
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-2 rounded" style="background:#e8f5e9">
                                <div class="small text-muted">Date In</div>
                                <div class="fw-bold text-success small">
                                    {{ $container->date_in ? \Carbon\Carbon::parse($container->date_in)->format('d M Y') : 'N/A' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded" style="background:#fce4ec">
                                <div class="small text-muted">Date Out</div>
                                <div class="fw-bold text-danger small">
                                    {{ $container->date_out ? \Carbon\Carbon::parse($container->date_out)->format('d M Y') : 'Active' }}
                                </div>
                            </div>
                        </div>
                        @if($container->date_in && $container->date_out)
                        <div class="col-12">
                            <div class="p-2 rounded bg-light">
                                <div class="small text-muted">Duration</div>
                                <div class="fw-bold">{{ \Carbon\Carbon::parse($container->date_in)->diffInDays($container->date_out) }} days</div>
                            </div>
                        </div>
                        @endif
                        <div class="col-12">
                            @php $s = $container->status ?? 'active'; @endphp
                            <span class="badge fs-6 {{ $s==='active' ? 'bg-success' : ($s==='closed' ? 'bg-secondary' : 'bg-warning text-dark') }}">
                                {{ ucfirst($s) }}
                            </span>
                        </div>
                    </div>

                    <hr>
                    {{-- Stock impact summary --}}
                    <div class="small text-muted fw-semibold mb-2"><i class="bi bi-graph-up-arrow me-1 text-success"></i>Stock Added on Arrival</div>
                    @forelse($container->items as $ci)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <span class="small text-truncate me-2">{{ $ci->item->name ?? '—' }}</span>
                        <span class="badge bg-success flex-shrink-0">+{{ $ci->quantity }}</span>
                    </div>
                    @empty
                    <div class="text-muted small">No items</div>
                    @endforelse
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <strong class="small">Total Qty Added</strong>
                        <span class="badge bg-success">+{{ $container->items->sum('quantity') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items table --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Items ({{ $container->items->count() }})</h6>
                    <span class="badge bg-secondary">Total qty: {{ $container->items->sum('quantity') }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Color</th>
                                <th>Part Number</th>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th class="text-center">Qty Added</th>
                                <th class="text-center">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($container->items as $ci)
                            <tr>
                                <td class="px-3">
                                    <span class="d-inline-block rounded-circle"
                                          style="width:12px;height:12px;background:{{ $ci->item->color_code ?? '#ccc' }}"></span>
                                </td>
                                <td><code class="small">{{ $ci->part_number ?? $ci->item->part_number ?? '—' }}</code></td>
                                <td class="fw-semibold">{{ $ci->item->name ?? 'N/A' }}</td>
                                <td class="text-muted small">{{ $ci->description ?? $ci->item->description ?? '' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success">+{{ $ci->quantity }}</span>
                                </td>
                                <td class="text-center">
                                    @if($ci->item)
                                        @php $stock = $ci->item->current_stock; $min = $ci->item->min_stock; @endphp
                                        <span class="badge {{ $stock <= 0 ? 'bg-danger' : ($stock < $min ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                            {{ $stock }} {{ $ci->item->unit }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No items in this container</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Zone stock info box --}}
            <div class="card mt-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>How Stock Logic Works</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background:#e8f5e9;border-left:4px solid #28a745">
                                <div class="fw-bold text-success mb-1"><i class="bi bi-box-seam me-1"></i>Container Arrives</div>
                                <div class="small text-muted">Items in container → item <code>current_stock</code> <strong class="text-success">increases ↑</strong></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background:#fce4ec;border-left:4px solid #dc3545">
                                <div class="fw-bold text-danger mb-1"><i class="bi bi-arrow-down-circle me-1"></i>Zone Stock IN</div>
                                <div class="small text-muted">Deploying item to zone → item <code>current_stock</code> <strong class="text-danger">decreases ↓</strong></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background:#e8f0ff;border-left:4px solid #0d6efd">
                                <div class="fw-bold text-primary mb-1"><i class="bi bi-arrow-up-circle me-1"></i>Zone Stock OUT</div>
                                <div class="small text-muted">Returning item from zone → item <code>current_stock</code> <strong class="text-success">increases ↑</strong></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background:#fff3cd;border-left:4px solid #ffc107">
                                <div class="fw-bold text-warning mb-1"><i class="bi bi-trash me-1"></i>Container Deleted</div>
                                <div class="small text-muted">Container removed → stock <strong class="text-danger">reversed ↓</strong> automatically</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
