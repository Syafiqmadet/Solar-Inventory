<?php
namespace App\Http\Controllers;

use App\Exports\ZoneExport;
use App\Models\Zone;
use App\Models\Item;
use App\Models\IsolatedItem;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ZoneController extends Controller
{
    use HasProjectScope;

    public function index()
    {
        $zones = Zone::query()->tap(fn($q) => $this->pidFilter($q))->withCount('transactions')->get()->map(function ($zone) {
            $zone->stockIn  = $zone->transactions()->where('type', 'in')->sum('quantity');
            $zone->stockOut = $zone->transactions()->where('type', 'out')->sum('quantity');
            return $zone;
        });
        return view('zones.index', compact('zones'));
    }

    public function exportAll()
    {
        if (!$this->pid()) return back()->with('error', 'No project selected.');
        return Excel::download(new ZoneExport(null, $this->pid()), 'Zones_All_'.now()->format('Y-m-d').'.xlsx');
    }

    public function export(Zone $zone)
    {
        return Excel::download(new ZoneExport($zone->id, $this->pid()), 'Zone_'.$zone->name.'_'.now()->format('Y-m-d').'.xlsx');
    }

    public function create() { return view('zones.create'); }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);
        $data = $request->all();
        $data['project_id'] = $this->pid();
        Zone::create($data);
        return redirect()->route('zones.index')->with('success', 'Zone created!');
    }

    public function show(Zone $zone)
    {
        $zone->load('transactions.item');
        $subcons = \App\Models\Subcon::with('mifs','mrfs')
            ->where('zone_id', $zone->id)
            ->where('project_id', session('current_project_id'))
            ->orderByDesc('created_at')->get();
        return view('zones.show', compact('zone','subcons'));
    }

    public function edit(Zone $zone) { return view('zones.edit', compact('zone')); }

    public function update(Request $request, Zone $zone)
    {
        $request->validate(['name' => 'required']);
        $zone->update($request->all());
        return redirect()->route('zones.index')->with('success', 'Zone updated!');
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();
        return redirect()->route('zones.index')->with('success', 'Zone deleted.');
    }

    public function stockForm(Zone $zone)
    {
        $items = Item::query()->tap(fn($q) => $this->pidFilter($q))->orderBy('name')->get();
        return view('zones.stock', compact('zone', 'items'));
    }

    public function stockTransaction(Request $request, Zone $zone)
    {
        $isOut     = $request->type === 'out';
        $outReason = $request->input('out_reason', 'normal');
        $isIsolate = $isOut && ($outReason === 'defect' || $outReason === 'damaged');

        $rules = [
            'item_id'  => 'required|exists:items,id',
            'type'     => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
        ];
        if ($isOut) $rules['out_reason'] = 'required|in:normal,defect,damaged';
        if ($isIsolate) $rules['isolate_reason'] = 'required|string|max:500';
        $request->validate($rules);

        $item = Item::findOrFail($request->item_id);

        if ($request->type === 'in' && $item->current_stock < $request->quantity) {
            return back()->withInput()->with('error', "Not enough stock to deploy! Available: {$item->current_stock} {$item->unit}");
        }

        $notes = $request->notes;
        if ($isIsolate) {
            $notes = trim("[{$outReason}] " . $request->isolate_reason . ($notes ? " | {$notes}" : ''));
        }

        StockTransaction::create([
            'item_id'  => $request->item_id,
            'zone_id'  => $zone->id,
            'type'     => $request->type,
            'quantity' => $request->quantity,
            'notes'    => $notes,
        ]);

        if ($request->type === 'in') {
            $item->decrement('current_stock', $request->quantity);
            $verb = 'deployed to zone';
        } elseif ($isIsolate) {
            IsolatedItem::create([
                'item_id'       => $item->id,
                'name'          => $item->name,
                'part_number'   => $item->part_number,
                'quantity'      => $request->quantity,
                'type'          => $outReason,
                'reason'        => $request->isolate_reason,
                'isolated_date' => now()->toDateString(),
                'status'        => 'isolated',
                'notes'         => $request->isolate_notes ?? $request->notes,
                'project_id'    => $this->pid(),
            ]);
            $verb = "removed from zone ({$outReason}) and added to Isolated Items";
        } else {
            $item->increment('current_stock', $request->quantity);
            $verb = 'returned to warehouse';
        }

        $newStock = $item->fresh()->current_stock;
        return redirect()->route('zones.show', $zone)->with('success', "{$request->quantity} × {$item->name} {$verb}. Warehouse stock: {$newStock}");
    }
}
