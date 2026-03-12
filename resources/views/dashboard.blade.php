@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-header">
    <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
    <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Solar Project Inventory Overview</p>
</div>
<div class="content-area">
    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card position-relative overflow-hidden border-0" style="background:linear-gradient(135deg,#667eea,#764ba2)">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small text-uppercase">Total Items</p>
                            <h2 class="mb-0 fw-bold">{{ $totalItems ?? 0 }}</h2>
                        </div>
                        <i class="bi bi-box-seam" style="font-size:2rem;opacity:0.3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card position-relative overflow-hidden border-0" style="background:linear-gradient(135deg,#f093fb,#f5576c)">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small text-uppercase">Zones</p>
                            <h2 class="mb-0 fw-bold">{{ $totalZones ?? 0 }}</h2>
                        </div>
                        <i class="bi bi-geo-alt" style="font-size:2rem;opacity:0.3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card position-relative overflow-hidden border-0" style="background:linear-gradient(135deg,#4facfe,#00f2fe)">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small text-uppercase">Containers</p>
                            <h2 class="mb-0 fw-bold">{{ $totalContainers ?? 0 }}</h2>
                        </div>
                        <i class="bi bi-archive" style="font-size:2rem;opacity:0.3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card position-relative overflow-hidden border-0" style="background:linear-gradient(135deg,#43e97b,#38f9d7)">
                <div class="card-body text-white p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small text-uppercase">Low Stock</p>
                            <h2 class="mb-0 fw-bold">{{ $lowStockItems ?? 0 }}</h2>
                        </div>
                        <i class="bi bi-exclamation-triangle" style="font-size:2rem;opacity:0.3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4" id="summary">
        <!-- Recent Transactions -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Stock Transactions</h6>
                    <a href="{{ route('zones.index') }}" class="btn btn-sm btn-outline-primary">View Zones</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr>
                                <th class="px-4">Date</th><th>Item</th><th>Zone</th><th>Type</th><th class="text-end px-4">Qty</th>
                            </tr></thead>
                            <tbody>
                                @forelse($recentTransactions ?? [] as $tx)
                                <tr>
                                    <td class="px-4 text-muted small">{{ $tx->created_at->format('d M Y') }}</td>
                                    <td>{{ $tx->item->name ?? '-' }}<br><small class="text-muted">{{ $tx->item->part_number ?? '' }}</small></td>
                                    <td>{{ $tx->zone->name ?? '-' }}</td>
                                    <td>
                                        @if($tx->type == 'in')
                                            <span class="badge bg-success">IN</span>
                                        @else
                                            <span class="badge bg-danger">OUT</span>
                                        @endif
                                    </td>
                                    <td class="text-end px-4 fw-bold">{{ $tx->quantity }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No transactions yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Low Stock Alerts</h6>
                </div>
                <div class="card-body">
                    @forelse($lowStockList ?? [] as $item)
                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background:#fff3cd">
                        <div>
                            <span class="fw-semibold small">{{ $item->name }}</span><br>
                            <small class="text-muted">{{ $item->part_number }}</small>
                        </div>
                        <span class="badge bg-warning text-dark">{{ $item->current_stock }}</span>
                    </div>
                    @empty
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-check-circle text-success" style="font-size:2rem"></i>
                        <p class="mt-2 mb-0 small">All items well stocked!</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-lightning me-2 text-warning"></i>Quick Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    @if($canWrite)
                    <a href="{{ route('items.create') }}" class="btn btn-solar btn-sm"><i class="bi bi-plus me-2"></i>Add New Item</a>
                    <a href="{{ route('zones.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-geo-alt me-2"></i>Add New Zone</a>
                    <a href="{{ route('containers.create') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-archive me-2"></i>Add Container</a>
                    @else
                    <p class="text-muted small text-center mb-0">👁 View-only mode</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Items Summary Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0"><i class="bi bi-table me-2 text-info"></i>Inventory Summary</h6>
                    <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-info">View All Items</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr>
                                <th class="px-4">Color</th><th>Part Number</th><th>Item Name</th><th>Description</th><th>Category</th><th class="text-center">Stock</th><th>Status</th>
                            </tr></thead>
                            <tbody>
                                @forelse($allItems ?? [] as $item)
                                <tr>
                                    <td class="px-4">
                                        <span class="color-dot" style="background-color:{{ $item->color_code ?? '#cccccc' }}" title="{{ $item->color_code }}"></span>
                                    </td>
                                    <td><code class="text-dark">{{ $item->part_number }}</code></td>
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td class="text-muted small">{{ Str::limit($item->description, 60) }}</td>
                                    <td><span class="badge bg-secondary">{{ $item->category ?? 'General' }}</span></td>
                                    <td class="text-center fw-bold">{{ $item->current_stock }}</td>
                                    <td>
                                        @if($item->current_stock <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif($item->current_stock <= ($item->min_stock ?? 5))
                                            <span class="badge bg-warning text-dark">Low Stock</span>
                                        @else
                                            <span class="badge bg-success">In Stock</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No items added yet. <a href="{{ route('items.create') }}">Add your first item</a></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
