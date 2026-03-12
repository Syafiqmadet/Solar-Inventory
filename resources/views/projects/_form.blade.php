@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label fw-semibold">Project Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $project->name ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Project Code <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control text-uppercase" placeholder="e.g. PROJ01" value="{{ old('code', $project->code ?? '') }}" required>
    </div>
    <div class="col-md-8">
        <label class="form-label fw-semibold">Location</label>
        <input type="text" name="location" class="form-control" placeholder="e.g. Kuala Lumpur" value="{{ old('location', $project->location ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Colour</label>
        <input type="color" name="color" class="form-control form-control-color w-100" value="{{ old('color', $project->color ?? '#FF6B35') }}">
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $project->description ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                {{ old('is_active', $project->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active (users can select this project)</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Assign Users</label>
        <div class="border rounded p-3" style="max-height:220px;overflow-y:auto;background:#fafafa">
            @foreach($users as $user)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="user_ids[]"
                    id="user_{{ $user->id }}" value="{{ $user->id }}"
                    {{ in_array($user->id, $assignedUserIds) ? 'checked' : '' }}>
                <label class="form-check-label" for="user_{{ $user->id }}">
                    {{ $user->name }}
                    <span class="badge ms-1 {{ $user->isAdmin() ? 'bg-danger' : ($user->isViewer() ? 'bg-secondary' : 'bg-primary') }}" style="font-size:0.7rem">{{ ucfirst($user->role) }}</span>
                    <span class="text-muted small">{{ $user->email }}</span>
                </label>
            </div>
            @endforeach
        </div>
        <div class="form-text">Admins automatically have access to all projects.</div>
    </div>
</div>
