@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'Containers')
@section('content')

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-archive me-2"></i>Containers</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Track shipping containers with batch, date in/out, parts &amp; quantities</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('containers.export', request()->query()) }}" class="btn btn-success fw-semibold me-2">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            </a>
            @if($canWrite)
            <a href="{{ route('containers.create') }}" class="btn btn-success fw-semibold me-2">
                <i class="bi bi-plus-circle me-2"></i>Add Container
            </a>
            @endif
        </div>
    </div>
</div>

<div class="content-area">

    {{-- Filter card --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">

                {{-- Search --}}
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">🔍 Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Container ID, batch, description, item name…"
                           value="{{ request('search') }}">
                </div>

                {{-- Batch --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">📦 Batch</label>
                    <input list="batch-list" name="batch" class="form-control form-control-sm"
                           placeholder="e.g. BATCH-001" value="{{ request('batch') }}">
                    <datalist id="batch-list">
                        @foreach($batches as $b)
                            <option value="{{ $b }}">
                        @endforeach
                    </datalist>
                </div>

                {{-- Item name --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">🔩 Item Name</label>
                    <input type="text" name="item_name" class="form-control form-control-sm"
                           placeholder="e.g. Solar Panel" value="{{ request('item_name') }}">
                </div>

                {{-- Status --}}
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active"  {{ request('status')=='active'  ? 'selected':'' }}>Active</option>
                        <option value="closed"  {{ request('status')=='closed'  ? 'selected':'' }}>Closed</option>
                        <option value="pending" {{ request('status')=='pending' ? 'selected':'' }}>Pending</option>
                    </select>
                </div>

                {{-- Date from --}}
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>

                {{-- Date to --}}
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-solar btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('containers.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Active filter badges --}}
    @php
        $activeFilters = array_filter([
            'Search'    => request('search'),
            'Batch'     => request('batch'),
            'Item'      => request('item_name'),
            'Status'    => request('status') ? ucfirst(request('status')) : null,
            'Date From' => request('date_from'),
            'Date To'   => request('date_to'),
        ]);
    @endphp
    @if(count($activeFilters))
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <small class="text-muted fw-semibold"><i class="bi bi-funnel me-1"></i>Filters:</small>
        @foreach($activeFilters as $label => $value)
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-normal px-2 py-1">
                {{ $label }}: <strong>{{ $value }}</strong>
            </span>
        @endforeach
        <a href="{{ route('containers.index') }}" class="btn btn-sm btn-outline-secondary py-0 px-2">✕ Clear all</a>
        <span class="ms-auto text-muted small"><i class="bi bi-file-earmark-excel me-1"></i>Export will match these filters</span>
    </div>
    @endif

    {{-- Table --}}
    <div class="card">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-archive me-2"></i>Containers</h6>
            <span class="badge bg-secondary">{{ $containers->total() }} total</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3" style="width:36px"></th>
                            <th>Container ID</th>
                            <th>Batch</th>
                            <th>Description</th>
                            <th>Date In</th>
                            <th>Date Out</th>
                            <th class="text-center">Items</th>
                            <th>Status</th>
                            <th class="px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($containers as $container)
                        <tr>
                            <td class="px-3">
                                <span class="d-inline-block rounded-circle"
                                      style="width:14px;height:14px;background:{{ $container->color_code ?? '#6c757d' }}"></span>
                            </td>
                            <td class="fw-bold"><code>{{ $container->container_id }}</code></td>
                            <td>
                                @if($container->batch)
                                    <span class="badge bg-info text-dark">{{ $container->batch }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ Str::limit($container->description, 40) }}</td>
                            <td>
                                @if($container->date_in)
                                    <span class="badge bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-calendar-check me-1"></i>{{ \Carbon\Carbon::parse($container->date_in)->format('d M Y') }}
                                    </span>
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($container->date_out)
                                    <span class="badge bg-danger bg-opacity-10 text-danger">
                                        <i class="bi bi-calendar-x me-1"></i>{{ \Carbon\Carbon::parse($container->date_out)->format('d M Y') }}
                                    </span>
                                @else <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark">{{ $container->items->count() }}</span>
                            </td>
                            <td>
                                @php $s = $container->status ?? 'active'; @endphp
                                @if($s === 'active')   <span class="badge bg-success">Active</span>
                                @elseif($s === 'closed') <span class="badge bg-secondary">Closed</span>
                                @else <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td class="px-3">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('containers.show', $container) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                    @if($canWrite)
                                    <a href="{{ route('containers.edit', $container) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('containers.destroy', $container) }}" method="POST" onsubmit="return confirm('Delete this container?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-archive" style="font-size:2.5rem;color:#ccc"></i>
                                <p class="mt-2 mb-0">No containers found.
                                    @if(count($activeFilters))
                                        <a href="{{ route('containers.index') }}">Clear filters</a> or
                                    @endif
                                    @if($canWrite)<a href="{{ route('containers.create') }}">add your first container</a>@endif
                                </p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($containers->hasPages())
        <div class="card-footer bg-white">{{ $containers->links() }}</div>
        @endif
    </div>
</div>
@endsection
