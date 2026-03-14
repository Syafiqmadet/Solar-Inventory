@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title', 'Subcontractors')
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-people me-2"></i>Subcontractors</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Manage subcontractors, material issue and return forms</p>
        </div>
        <a href="{{ route('subcon.material.report') }}" class="btn btn-success fw-semibold me-2">
            <i class="bi bi-file-earmark-excel me-2"></i>Material Report
        </a>
        @if($canWrite)
        <button class="btn btn-light fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAddSubcon">
            <i class="bi bi-plus-circle me-2"></i>Add Subcon
        </button>
        @endif
    </div>
</div>

<div class="content-area">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-success mb-0">{{ $subcons->where('status','active')->count() }}</h4>
                <small class="text-muted">Active Subcons</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-secondary mb-0">{{ $subcons->where('status','completed')->count() }}</h4>
                <small class="text-muted">Completed</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-primary mb-0">{{ $subcons->count() }}</h4>
                <small class="text-muted">Total Subcons</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h4 class="text-warning mb-0">{{ $subcons->where('status','terminated')->count() }}</h4>
                <small class="text-muted">Terminated</small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3"><h6 class="mb-0"><i class="bi bi-people me-2"></i>Subcontractor List</h6></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr>
                    <th class="px-4">Company</th>
                    <th>Supervisor</th>
                    <th>Zone</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th class="text-center">MIF</th>
                    <th class="text-center">MRF</th>
                    <th class="text-center">Actions</th>
                </tr></thead>
                <tbody>
                @forelse($subcons as $sc)
                <tr>
                    <td class="px-4 fw-semibold">{{ $sc->name }}</td>
                    <td>
                        <div>{{ $sc->supervisor_name ?? '—' }}</div>
                        @if($sc->supervisor_contact)<small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $sc->supervisor_contact }}</small>@endif
                    </td>
                    <td>{{ $sc->zone->name ?? '<span class="text-muted">—</span>' }}</td>
                    <td class="small text-muted">
                        {{ $sc->start_date?->format('d M Y') ?? '—' }} → {{ $sc->end_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td>{!! $sc->status_badge !!}</td>
                    <td class="text-center">
                        <a href="{{ route('subcon.mif', $sc) }}" class="badge bg-primary text-decoration-none">
                            {{ $sc->mifs()->count() }} MIF
                        </a>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('subcon.mrf', $sc) }}" class="badge bg-info text-decoration-none">
                            {{ $sc->mrfs()->count() }} MRF
                        </a>
                    </td>
                    <td class="text-center">
                        @if($canWrite)
                        <button class="btn btn-sm btn-outline-secondary btn-edit-subcon"
                            data-id="{{ $sc->id }}"
                            data-name="{{ $sc->name }}"
                            data-supervisor_name="{{ $sc->supervisor_name }}"
                            data-supervisor_contact="{{ $sc->supervisor_contact }}"
                            data-zone_id="{{ $sc->zone_id }}"
                            data-start_date="{{ $sc->start_date?->format('Y-m-d') }}"
                            data-end_date="{{ $sc->end_date?->format('Y-m-d') }}"
                            data-status="{{ $sc->status }}"
                            data-notes="{{ $sc->notes }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('subcon.destroy', $sc) }}" class="d-inline"
                            onsubmit="return confirm('Delete this subcontractor and all its forms?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-5">
                    <i class="bi bi-people display-6 d-block mb-2 opacity-25"></i>No subcontractors yet.
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add Modal --}}
@if($canWrite)
<div class="modal fade" id="modalAddSubcon" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--solar-dark);color:#fff">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Subcontractor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('subcon.store') }}">
                @csrf
                <div class="modal-body">
                    @include('subcon._form', ['subcon' => null, 'zones' => $zones])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="modalEditSubcon" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--solar-dark);color:#fff">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Subcontractor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editSubconForm">
                @csrf @method('PUT')
                <div class="modal-body">
                    @include('subcon._form', ['subcon' => null, 'zones' => $zones])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
document.querySelectorAll('.btn-edit-subcon').forEach(btn => {
    btn.addEventListener('click', function() {
        const d = this.dataset;
        const form = document.getElementById('editSubconForm');
        form.action = '/subcon/' + d.id;
        form.querySelector('[name=name]').value               = d.name;
        form.querySelector('[name=supervisor_name]').value    = d.supervisor_name;
        form.querySelector('[name=supervisor_contact]').value = d.supervisor_contact;
        form.querySelector('[name=zone_id]').value            = d.zone_id;
        form.querySelector('[name=start_date]').value         = d.start_date;
        form.querySelector('[name=end_date]').value           = d.end_date;
        form.querySelector('[name=status]').value             = d.status;
        form.querySelector('[name=notes]').value              = d.notes;
        new bootstrap.Modal(document.getElementById('modalEditSubcon')).show();
    });
});
</script>
@endsection
