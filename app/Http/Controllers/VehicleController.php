<?php
namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\DieselUsage;
use App\Models\FuelRecord;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    private function pid() { return session('current_project_id'); }
    private function pidFilter($query) { $pid = $this->pid(); return $pid ? $query->where('project_id', $pid) : $query; }

    /** Helper: get purchased & used totals + balance for a given fuel type */
    private function fuelStats(string $type): array
    {
        $purchased = (float) FuelRecord::query()->tap(fn($q) => $this->pidFilter($q))->where('fuel_type', $type)->sum('liters');
        $pid  = $this->pid();
        $used      = (float) DieselUsage::whereHas('vehicle', fn($q) => $pid ? $q->where('project_id', $pid) : $q)->where('fuel_type', $type)->sum('liters_used');
        return [
            'purchased' => $purchased,
            'used'      => $used,
            'balance'   => $purchased - $used,
        ];
    }

    public function index()
    {
        $pid = session('current_project_id'); $vehicles = Vehicle::query()->tap(fn($q) => $this->pidFilter($q))->with('dieselUsages')->orderBy('vehicle_no')->get();
        $diesel   = $this->fuelStats('diesel');
        $petrol   = $this->fuelStats('petrol');

        return view('vehicles.index', compact('vehicles','diesel','petrol'));
    }

    public function create() { return view('vehicles.create'); }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_no' => 'required|string|max:20',
            'name'       => 'required|string|max:100',
            'type'       => 'required|string|max:50',
        ]);
        $data = $request->all();
        $data['project_id'] = $this->pid();
        Vehicle::create($data);
        return redirect()->route('vehicles.index')->with('success', 'Vehicle added!');
    }

    public function show(Vehicle $vehicle)
    {
        $usages  = $vehicle->dieselUsages()->orderByDesc('date')->orderByDesc('id')->paginate(20);
        $diesel  = $this->fuelStats('diesel');
        $petrol  = $this->fuelStats('petrol');

        $vehicleDieselUsed  = (float) DieselUsage::where('vehicle_id',$vehicle->id)->where('fuel_type','diesel')->sum('liters_used');
        $vehiclePetrolUsed  = (float) DieselUsage::where('vehicle_id',$vehicle->id)->where('fuel_type','petrol')->sum('liters_used');

        return view('vehicles.show', compact(
            'vehicle','usages','diesel','petrol',
            'vehicleDieselUsed','vehiclePetrolUsed'
        ));
    }

    public function edit(Vehicle $vehicle) { return view('vehicles.edit', compact('vehicle')); }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'vehicle_no' => 'required|string|max:20',
            'name'       => 'required|string|max:100',
            'type'       => 'required|string|max:50',
        ]);
        $vehicle->update($request->all());
        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated!');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted.');
    }

    // ── Log Usage Form ────────────────────────────────────────────
    public function usageForm(Vehicle $vehicle)
    {
        $diesel = $this->fuelStats('diesel');
        $petrol = $this->fuelStats('petrol');
        return view('vehicles.usage', compact('vehicle','diesel','petrol'));
    }

    // ── Store Usage ───────────────────────────────────────────────
    public function usageStore(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'fuel_type'   => 'required|in:petrol,diesel',
            'date'        => 'required|date',
            'liters_used' => 'required|numeric|min:0.01',
            'driver_name' => 'nullable|string|max:100',
            'odometer_km' => 'nullable|integer|min:0',
            'purpose'     => 'nullable|string|max:255',
            'notes'       => 'nullable|string',
        ]);

        $stats = $this->fuelStats($request->fuel_type);

        if ($request->liters_used > $stats['balance']) {
            return back()->withInput()->with('error',
                'Insufficient '.ucfirst($request->fuel_type).'! Available: '.number_format($stats['balance'],2).' L'
            );
        }

        DieselUsage::create([
            'vehicle_id'     => $vehicle->id,
            'fuel_type'      => $request->fuel_type,
            'date'           => $request->date,
            'liters_used'    => $request->liters_used,
            'balance_before' => $stats['balance'],
            'balance_after'  => $stats['balance'] - $request->liters_used,
            'driver_name'    => $request->driver_name,
            'odometer_km'    => $request->odometer_km,
            'purpose'        => $request->purpose,
            'notes'          => $request->notes,
        ]);

        $after = number_format($stats['balance'] - $request->liters_used, 2);
        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', ucfirst($request->fuel_type).' usage recorded! Remaining: '.$after.' L');
    }

    // ── Delete Usage ──────────────────────────────────────────────
    public function usageDestroy(Vehicle $vehicle, DieselUsage $usage)
    {
        $fuelType = $usage->fuel_type;
        $usage->delete();
        $this->recalculateBalances($fuelType);
        return back()->with('success', 'Usage record deleted. Balances recalculated.');
    }

    private function recalculateBalances(string $fuelType)
    {
        $totalPurchased = (float) FuelRecord::where('fuel_type', $fuelType)->sum('liters');
        $usages = DieselUsage::where('fuel_type', $fuelType)->orderBy('date')->orderBy('id')->get();
        $running = $totalPurchased;
        foreach ($usages as $u) {
            $u->balance_before = $running;
            $u->balance_after  = $running - $u->liters_used;
            $running           = $u->balance_after;
            $u->saveQuietly();
        }
    }
}
