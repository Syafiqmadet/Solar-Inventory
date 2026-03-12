@php $canWrite = auth()->check() && auth()->user()->canWrite(); @endphp
@extends('layouts.app')
@section('title','Projects')
@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-folder2-open me-2"></i>Projects</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">Manage projects and user assignments</p>
        </div>
        @if($canWrite)
        <a href="{{ route('projects.create') }}" class="btn btn-light"><i class="bi bi-plus-lg me-2"></i>New Project</a>
        @endif
    </div>
</div>
<div class="content-area">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            @if($projects->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-folder-x" style="font-size:3rem;color:#ccc"></i>
                <p class="mt-3">No projects yet. <a href="{{ route('projects.create') }}">Create your first project</a></p>
            </div>
            @else
            <table class="table table-hover mb-0">
                <thead style="background:#f8f9fa">
                    <tr>
                        <th class="px-4 py-3">Project</th>
                        <th>Code</th>
                        <th>Location</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th class="px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($projects as $project)
                <tr class="{{ $project->is_active ? '' : 'table-secondary text-muted' }}">
                    <td class="px-4 py-3">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:12px;height:12px;border-radius:50%;background:{{ $project->is_active ? $project->color : '#aaa' }};flex-shrink:0"></div>
                            <span class="fw-semibold {{ $project->is_active ? '' : 'text-decoration-line-through text-muted' }}">{{ $project->name }}</span>
                            @if(!$project->is_active)
                            <span class="badge bg-secondary ms-1" style="font-size:0.7rem">Archived</span>
                            @endif
                        </div>
                        @if($project->description)<div class="text-muted small">{{ Str::limit($project->description,60) }}</div>@endif
                    </td>
                    <td><code>{{ $project->code }}</code></td>
                    <td class="text-muted small">{{ $project->location ?? '—' }}</td>
                    <td><span class="badge bg-primary">{{ $project->users_count }} user{{ $project->users_count != 1 ? 's' : '' }}</span></td>
                    <td>
                        @if($project->is_active)
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                        @else
                        <span class="badge bg-secondary"><i class="bi bi-archive me-1"></i>Archived</span>
                        @endif
                    </td>
                    <td class="px-4">
                        <div class="d-flex gap-1">
                            @if($canWrite)
                            {{-- Archive / Restore toggle --}}
                            @php
                                $toggleMsg = $project->is_active
                                    ? "Archive project {$project->name}? Users will no longer be able to select it."
                                    : "Restore project {$project->name}? It will become visible to users again.";
                            @endphp
                            <form action="{{ route('projects.toggle', $project) }}" method="POST"
                                  onsubmit="return confirm(this.dataset.msg)" data-msg="{{ $toggleMsg }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm {{ $project->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}"
                                        title="{{ $project->is_active ? 'Archive (hide from users)' : 'Restore (make visible again)' }}">
                                    <i class="bi {{ $project->is_active ? 'bi-archive' : 'bi-arrow-counterclockwise' }}"></i>
                                    {{ $project->is_active ? 'Archive' : 'Restore' }}
                                </button>
                            </form>

                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @php $deleteMsg = "Permanently delete project {$project->name}? This cannot be undone."; @endphp
                            <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                  onsubmit="return confirm(this.dataset.msg)" data-msg="{{ $deleteMsg }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    <div class="mt-3 text-muted small">
        <i class="bi bi-info-circle me-1"></i>
        Archived projects are hidden from the project selection screen. Users currently on an archived project will be redirected automatically.
    </div>
</div>
@endsection
