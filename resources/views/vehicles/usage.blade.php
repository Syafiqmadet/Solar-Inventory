@extends('layouts.app')
@section('title', 'Log Fuel Usage')

@section('styles')
<style>
.fuel-toggle { display:none; }
.fuel-toggle + label {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding:18px 28px; border:2px solid #dee2e6; border-radius:12px;
    cursor:pointer; transition:all .2s; min-width:140px; gap:6px;
}
.fuel-toggle + label .icon { font-size:2rem; }
.fuel-toggle + label .lbl  { font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; }
#type_petrol:checked + label { border-color:#28a745; background:#e8f8ee; color:#28a745; }
#type_diesel:checked + label { border-color:#0d6efd; background:#e8f0ff; color:#0d6efd; }
.balance-panel { border-radius:14px; padding:20px; text-align:center; transition:all .3s; }
.balance-panel.petrol-panel { background:linear-gradient(135deg,#28a745,#20c997); }
.balance-panel.diesel-panel { background:linear-gradient(135deg,#0d6efd,#0dcaf0); }
.balance-panel.low-panel    { background:linear-gradient(135deg,#ffc107,#fd7e14); }
.balance-panel.empty-panel  { background:linear-gradient(135deg,#dc3545,#e83e8c); }
.balance-display { font-size:2.8rem; font-weight:800; line-height:1; }
.after-preview { border-radius:12px; padding:14px 18px; transition:all .2s; }
.after-preview.ok      { background:#f0fdf4; border:2px solid #86efac; }
.after-preview.warn    { background:#fffbeb; border:2px solid #fde68a; }
.after-preview.danger  { background:#fff5f5; border:2px solid #fca5a5; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-fuel-pump me-2"></i>Log Fuel Usage</h1>
            <p class="mb-0 mt-1" style="color:rgba(255,255,255,0.85)">
                {{ $vehicle->type_icon }} {{ $vehicle->name }} &nbsp;·&nbsp;
                <code style="color:rgba(255,255,255,0.9)">{{ $vehicle->vehicle_no }}</code>
            </p>
        </div>
        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-outline-light">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="content-area">
    <div class="row justify-content-center g-4">

        {{-- Left: Balance Panel + Vehicle Info --}}
        <div class="col-md-4">

            {{-- Dynamic balance panel (JS-controlled) --}}
            <div id="balancePanel" class="balance-panel petrol-panel text-white mb-3">
                <p class="mb-1 opacity-75 small text-uppercase fw-semibold" id="balanceLabel">Petrol Balance</p>
                <div class="balance-display" id="balanceDisplay">{{ number_format($petrol['balance'],1) }}</div>
                <p class="mt-1 mb-2 opacity-75 small">Liters available</p>
                <div id="balanceStatus" class="fw-semibold small">
                    @if($petrol['balance'] <= 0) ⚠️ No petrol stock!
                    @elseif($petrol['balance'] < 50) ⚠️ Low — refill soon
                    @else ✅ Stock sufficient
                    @endif
                </div>
            </div>

            {{-- After-use preview --}}
            <div id="afterPreview" class="after-preview ok mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small mb-1">Balance Before</div>
                        <div class="fw-bold text-primary fs-5" id="beforeText">{{ number_format($petrol['balance'],2) }} L</div>
                    </div>
                    <div class="text-muted">→</div>
                    <div class="text-end">
                        <div class="text-muted small mb-1">Balance After</div>
                        <div class="fw-bold fs-5" id="afterText" style="color:#16a34a">— L</div>
                    </div>
                </div>
                <div id="afterWarn" class="mt-2 d-none small fw-semibold text-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i><span id="afterWarnText"></span>
                </div>
            </div>

            {{-- Vehicle Info --}}
            <div class="card">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">{{ $vehicle->type_icon }} {{ $vehicle->name }}</h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Plate</small><code>{{ $vehicle->vehicle_no }}</code>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Type</small>
                            <span class="badge bg-light text-dark border">{{ $vehicle->type }}</span>
                        </div>
                        @if($vehicle->brand)
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Brand</small>
                            <span class="small">{{ $vehicle->brand }} {{ $vehicle->model }}</span>
                        </div>
                        @endif
                        <hr class="my-1">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">⛽ Petrol used</small>
                            <span class="fw-semibold text-success small">{{ number_format(App\Models\DieselUsage::where("vehicle_id",$vehicle->id)->where("fuel_type","petrol")->sum("liters_used"), 1) }} L</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">🛢️ Diesel used</small>
                            <span class="fw-semibold text-primary small">{{ number_format((App\Models\DieselUsage::where('vehicle_id',$vehicle->id)->where('fuel_type','diesel')->sum('liters_used')), 1) }} L</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Form --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-journal-plus me-2 text-primary"></i>Record Fuel Usage</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('vehicles.usage.store', $vehicle) }}" method="POST" id="usageForm">
                        @csrf

                        {{-- Fuel Type Selector --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Fuel Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div>
                                    <input type="radio" name="fuel_type" value="petrol" class="fuel-toggle" id="type_petrol"
                                           {{ old('fuel_type','petrol')==='petrol' ? 'checked' : '' }}>
                                    <label for="type_petrol">
                                        <span class="icon">⛽</span>
                                        <span class="lbl">Petrol</span>
                                        <small class="opacity-75">{{ number_format($petrol['balance'],1) }} L left</small>
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" name="fuel_type" value="diesel" class="fuel-toggle" id="type_diesel"
                                           {{ old('fuel_type')==='diesel' ? 'checked' : '' }}>
                                    <label for="type_diesel">
                                        <span class="icon">🛢️</span>
                                        <span class="lbl">Diesel</span>
                                        <small class="opacity-75">{{ number_format($diesel['balance'],1) }} L left</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control"
                                       value="{{ old('date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Liters Used <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="liters_used" id="litersUsed"
                                           class="form-control @error('liters_used') is-invalid @enderror"
                                           value="{{ old('liters_used') }}" step="0.01" min="0.01"
                                           placeholder="0.00" required>
                                    <span class="input-group-text">L</span>
                                </div>
                                @error('liters_used')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Driver Name</label>
                                <input type="text" name="driver_name" class="form-control"
                                       value="{{ old('driver_name') }}" placeholder="Full name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Odometer (km)</label>
                                <div class="input-group">
                                    <input type="number" name="odometer_km" class="form-control"
                                           value="{{ old('odometer_km') }}" min="0" placeholder="Current reading">
                                    <span class="input-group-text">km</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Purpose / Trip</label>
                                <input type="text" name="purpose" class="form-control"
                                       value="{{ old('purpose') }}" placeholder="e.g. Delivery to Zone A, Site work...">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Optional remarks...">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-12 d-flex gap-2 justify-content-end mt-2">
                                <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" id="submitBtn" class="btn btn-solar px-4">
                                    <i class="bi bi-check-circle me-2"></i>Record Usage
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const balances = {
    petrol: {{ $petrol['balance'] }},
    diesel: {{ $diesel['balance'] }}
};

const panel      = document.getElementById('balancePanel');
const balLabel   = document.getElementById('balanceLabel');
const balDisplay = document.getElementById('balanceDisplay');
const balStatus  = document.getElementById('balanceStatus');
const beforeText = document.getElementById('beforeText');
const afterText  = document.getElementById('afterText');
const afterPrev  = document.getElementById('afterPreview');
const afterWarn  = document.getElementById('afterWarn');
const afterWarnT = document.getElementById('afterWarnText');
const submitBtn  = document.getElementById('submitBtn');
const litersInp  = document.getElementById('litersUsed');

let currentFuel = 'petrol';

function updatePanel(fuel) {
    currentFuel = fuel;
    const bal = balances[fuel];
    const icon = fuel === 'petrol' ? '⛽' : '🛢️';
    balLabel.textContent  = icon + ' ' + (fuel.charAt(0).toUpperCase()+fuel.slice(1)) + ' Balance';
    balDisplay.textContent = bal.toFixed(1);

    panel.className = 'balance-panel text-white mb-3 ';
    if (bal <= 0)       panel.className += 'empty-panel';
    else if (bal < 50)  panel.className += 'low-panel';
    else                panel.className += fuel+'-panel';

    balStatus.textContent = bal <= 0 ? '⚠️ No '+fuel+' stock!'
                          : bal < 50 ? '⚠️ Low — refill soon'
                          : '✅ Stock sufficient';

    beforeText.textContent = bal.toFixed(2) + ' L';
    litersInp.max = bal;
    calcAfter();
}

function calcAfter() {
    const used = parseFloat(litersInp.value) || 0;
    const bal  = balances[currentFuel];
    const after = bal - used;

    if (used <= 0) {
        afterText.textContent = '— L';
        afterText.style.color = '#6c757d';
        afterPrev.className = 'after-preview ok';
        afterWarn.classList.add('d-none');
        submitBtn.disabled = balances[currentFuel] <= 0;
        return;
    }

    afterText.textContent = after.toFixed(2) + ' L';

    if (used > bal) {
        afterText.style.color = '#dc3545';
        afterPrev.className = 'after-preview danger';
        afterWarn.classList.remove('d-none');
        afterWarnT.textContent = 'Exceeds available stock by ' + (used - bal).toFixed(2) + ' L!';
        submitBtn.disabled = true;
    } else if (after < 50) {
        afterText.style.color = '#d97706';
        afterPrev.className = 'after-preview warn';
        afterWarn.classList.remove('d-none');
        afterWarnT.textContent = 'Warning: balance will be low after this usage.';
        afterWarnT.style.color = '#d97706';
        submitBtn.disabled = false;
    } else {
        afterText.style.color = '#16a34a';
        afterPrev.className = 'after-preview ok';
        afterWarn.classList.add('d-none');
        submitBtn.disabled = false;
    }
}

document.querySelectorAll('input[name=fuel_type]').forEach(r => {
    r.addEventListener('change', () => updatePanel(r.value));
});
litersInp.addEventListener('input', calcAfter);

// Init
updatePanel('{{ old("fuel_type","petrol") }}');
</script>
@endsection
