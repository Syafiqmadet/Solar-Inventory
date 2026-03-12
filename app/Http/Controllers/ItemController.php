<?php
namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Models\Item;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{
    use HasProjectScope;

    public function index(Request $request)
    {
        $query = Item::query()->tap(fn($q) => $this->pidFilter($q));
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name',        'like', '%'.$request->search.'%')
                  ->orWhere('part_number','like', '%'.$request->search.'%')
                  ->orWhere('description','like', '%'.$request->search.'%');
            });
        }
        if ($request->category) $query->where('category', $request->category);
        if ($request->stock === 'low') $query->whereRaw('current_stock <= min_stock AND current_stock > 0');
        if ($request->stock === 'out') $query->where('current_stock', '<=', 0);
        if ($request->stock === 'ok')  $query->whereRaw('current_stock > min_stock');

        $items = $query->orderBy('name')->paginate(20)->withQueryString();
        return view('items.index', compact('items'));
    }

    public function export(Request $request)
    {
        if (!$this->pid()) return back()->with('error', 'No project selected.');
        $filename = 'Inventory_'.now()->format('Y-m-d').'.xlsx';
        return Excel::download(
            new InventoryExport($request->search, $request->category, $request->stock, $this->pid()),
            $filename
        );
    }

    public function create() { return view('items.create'); }

    public function store(Request $request)
    {
        $request->validate([
            'part_number' => 'required|unique:items',
            'name'        => 'required',
        ]);
        $data = $request->all();
        $data['project_id'] = $this->pid();
        Item::create($data);
        return redirect()->route('items.index')->with('success', 'Item added successfully!');
    }

    public function show(Item $item)
    {
        $item->load('transactions.zone');
        return view('items.show', compact('item'));
    }

    public function edit(Item $item) { return view('items.edit', compact('item')); }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'part_number' => 'required|unique:items,part_number,'.$item->id,
            'name'        => 'required',
        ]);
        $item->update($request->all());
        return redirect()->route('items.index')->with('success', 'Item updated successfully!');
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()->route('items.index')->with('success', 'Item deleted.');
    }
}
