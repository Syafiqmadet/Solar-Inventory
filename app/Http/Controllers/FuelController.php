<?php
namespace App\Http\Controllers;

use App\Models\FuelRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FuelController extends Controller
{
    private function pid() { return session('current_project_id'); }
    private function pidFilter($query, $col = 'project_id') { $pid = $this->pid(); return $pid ? $query->where($col, $pid) : $query; }

    public function index(Request $request)
    {
        $query = FuelRecord::query()->tap(fn($q) => $this->pidFilter($q))->latest('date');

        if ($request->fuel_type) {
            $query->where('fuel_type', $request->fuel_type);
        }
        if ($request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('do_number', 'like', '%'.$request->search.'%')
                  ->orWhere('supplier', 'like', '%'.$request->search.'%')
                  ->orWhere('vehicle_no', 'like', '%'.$request->search.'%');
            });
        }

        $records = $query->paginate(15);

        // Summary stats
        $totalPetrolLiters = FuelRecord::query()->tap(fn($q) => $this->pidFilter($q))->where('fuel_type','petrol')->sum('liters');
        $totalDieselLiters = FuelRecord::query()->tap(fn($q) => $this->pidFilter($q))->where('fuel_type','diesel')->sum('liters');
        $totalPetrolRM     = FuelRecord::query()->tap(fn($q) => $this->pidFilter($q))->where('fuel_type','petrol')->sum('amount_rm');
        $totalDieselRM     = FuelRecord::query()->tap(fn($q) => $this->pidFilter($q))->where('fuel_type','diesel')->sum('amount_rm');
        $totalSpend        = $totalPetrolRM + $totalDieselRM;
        $totalRecords      = FuelRecord::query()->tap(fn($q) => $this->pidFilter($q))->count();

        return view('fuel.index', compact(
            'records',
            'totalPetrolLiters','totalDieselLiters',
            'totalPetrolRM','totalDieselRM',
            'totalSpend','totalRecords'
        ));
    }

    public function create()
    {
        return view('fuel.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fuel_type'  => 'required|in:petrol,diesel',
            'date'       => 'required|date',
            'liters'     => 'required|numeric|min:0.01',
            'amount_rm'  => 'required|numeric|min:0.01',
            'do_number'  => 'nullable|string|max:100',
            'do_image'   => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'supplier'   => 'nullable|string|max:150',
            'vehicle_no' => 'nullable|string|max:50',
            'notes'      => 'nullable|string',
        ]);

        if ($request->hasFile('do_image')) {
            $file = $request->file('do_image');
            $validated['do_image'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file));
        }

        FuelRecord::create(array_merge($validated, ['project_id' => $this->pid()]));

        return redirect()->route('fuel.index')
            ->with('success', 'Fuel record added successfully!');
    }

    public function show(FuelRecord $fuel)
    {
        return view('fuel.show', compact('fuel'));
    }

    public function edit(FuelRecord $fuel)
    {
        return view('fuel.edit', compact('fuel'));
    }

    public function update(Request $request, FuelRecord $fuel)
    {
        $validated = $request->validate([
            'fuel_type'  => 'required|in:petrol,diesel',
            'date'       => 'required|date',
            'liters'     => 'required|numeric|min:0.01',
            'amount_rm'  => 'required|numeric|min:0.01',
            'do_number'  => 'nullable|string|max:100',
            'do_image'   => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'supplier'   => 'nullable|string|max:150',
            'vehicle_no' => 'nullable|string|max:50',
            'notes'      => 'nullable|string',
        ]);

        if ($request->hasFile('do_image')) {
            // Delete old image if exists
            if ($fuel->do_image) {
                Storage::disk('public')->delete($fuel->do_image);
            }
            $path = $request->file('do_image')->store('fuel-do', 'public');
            $validated['do_image'] = $path;
        }

        $fuel->update($validated);

        return redirect()->route('fuel.index')
            ->with('success', 'Fuel record updated!');
    }

    public function destroy(FuelRecord $fuel)
    {
        $fuel->delete();
        return redirect()->route('fuel.index')
            ->with('success', 'Fuel record deleted.');
    }

    public function deleteImage(FuelRecord $fuel)
    {
        if ($fuel->do_image) {
            $fuel->update(['do_image' => null]);
        }
        return back()->with('success', 'DO image removed.');
    }
}
