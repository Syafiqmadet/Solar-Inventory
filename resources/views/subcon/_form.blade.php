<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $subcon?->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Zone</label>
        <select name="zone_id" class="form-select">
            <option value="">— No specific zone —</option>
            @foreach($zones as $z)
            <option value="{{ $z->id }}" {{ old('zone_id', $subcon?->zone_id) == $z->id ? 'selected' : '' }}>{{ $z->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Supervisor Name</label>
        <input type="text" name="supervisor_name" class="form-control" value="{{ old('supervisor_name', $subcon?->supervisor_name) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Supervisor Contact</label>
        <input type="text" name="supervisor_contact" class="form-control" value="{{ old('supervisor_contact', $subcon?->supervisor_contact) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $subcon?->start_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $subcon?->end_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select">
            <option value="active"     {{ old('status', $subcon?->status ?? 'active') == 'active'     ? 'selected' : '' }}>Active</option>
            <option value="completed"  {{ old('status', $subcon?->status) == 'completed'  ? 'selected' : '' }}>Completed</option>
            <option value="terminated" {{ old('status', $subcon?->status) == 'terminated' ? 'selected' : '' }}>Terminated</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $subcon?->notes) }}</textarea>
    </div>
</div>
