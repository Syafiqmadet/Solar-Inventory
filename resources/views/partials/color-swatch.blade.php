@php
$colors = [
    '#EF4444' => 'Red',      '#F97316' => 'Orange',  '#F59E0B' => 'Amber',
    '#EAB308' => 'Yellow',   '#84CC16' => 'Lime',     '#22C55E' => 'Green',
    '#14B8A6' => 'Teal',     '#06B6D4' => 'Cyan',     '#3B82F6' => 'Blue',
    '#6366F1' => 'Indigo',   '#A855F7' => 'Purple',   '#EC4899' => 'Pink',
    '#6B7280' => 'Gray',     '#1F2937' => 'Dark',
];
$currentColor = $default ?? '#3B82F6';
@endphp

<label class="form-label fw-semibold">{{ $label ?? 'Color' }}</label>
<input type="hidden" name="{{ $fieldName }}" id="{{ $fieldId }}" value="{{ $currentColor }}">

<div class="d-flex flex-wrap gap-2 py-1">
    @foreach($colors as $hex => $name)
    <div class="swatch-dot"
         data-hex="{{ $hex }}"
         data-field="{{ $fieldId }}"
         data-name="{{ $name }}"
         title="{{ $name }}"
         style="width:30px;height:30px;border-radius:6px;background:{{ $hex }};cursor:pointer;
                border:3px solid {{ strtoupper($currentColor)===strtoupper($hex) ? '#111' : 'rgba(0,0,0,0.08)' }};
                box-shadow:{{ strtoupper($currentColor)===strtoupper($hex) ? '0 0 0 2px rgba(0,0,0,0.25)' : 'none' }};
                transition:all .15s;flex-shrink:0">
    </div>
    @endforeach
</div>

<div class="d-flex align-items-center gap-2 mt-1">
    <span class="d-inline-block rounded" id="{{ $fieldId }}_preview"
          style="width:20px;height:20px;background:{{ $currentColor }};border:1px solid #ccc;flex-shrink:0"></span>
    <small class="text-muted">
        <span id="{{ $fieldId }}_label">{{ $colors[strtoupper($currentColor)] ?? $colors[$currentColor] ?? $currentColor }}</span>
    </small>
</div>

@once
@push('scripts')
<script>
document.addEventListener('click', function(e) {
    const dot = e.target.closest('.swatch-dot');
    if (!dot) return;
    const hex   = dot.dataset.hex;
    const field = dot.dataset.field;
    const name  = dot.dataset.name;

    // Update hidden input
    document.getElementById(field).value = hex;

    // Update preview
    document.getElementById(field + '_preview').style.background = hex;
    document.getElementById(field + '_label').textContent = name;

    // Update borders on all swatches for this field
    document.querySelectorAll('.swatch-dot[data-field="' + field + '"]').forEach(function(s) {
        const active = s.dataset.hex === hex;
        s.style.border = active ? '3px solid #111' : '3px solid rgba(0,0,0,0.08)';
        s.style.boxShadow = active ? '0 0 0 2px rgba(0,0,0,0.25)' : 'none';
    });
});
</script>
@endpush
@endonce
