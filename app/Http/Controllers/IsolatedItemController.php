<?php
namespace App\Http\Controllers;

use App\Models\IsolatedItem;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IsolatedItemController extends Controller
{
    private function pid() { return session('current_project_id'); }
    private function pidFilter($query) { $pid = $this->pid(); return $pid ? $query->where('project_id', $pid) : $query; }

    public function index(Request $request)
    {
        $pid   = session('current_project_id');
        $query = IsolatedItem::with('item')->tap(fn($q) => $this->pidFilter($q));

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name',        'like', '%'.$request->search.'%')
                  ->orWhere('part_number','like', '%'.$request->search.'%')
                  ->orWhere('reason',    'like', '%'.$request->search.'%');
            });
        }
        if ($request->type)   $query->where('type',   $request->type);
        if ($request->status) $query->where('status', $request->status);
        if ($request->date_from) $query->whereDate('isolated_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('isolated_date', '<=', $request->date_to);

        $isolated = $query->latest('isolated_date')
            ->select(['id','project_id','item_id','name','part_number','quantity','type','reason','isolated_date','status','notes','created_at','updated_at'])
            ->paginate(15)->withQueryString();

        $stats = [
            'total'    => IsolatedItem::query()->tap(fn($q) => $this->pidFilter($q))->count(),
            'defect'   => IsolatedItem::query()->tap(fn($q) => $this->pidFilter($q))->where('type','defect')->count(),
            'damaged'  => IsolatedItem::query()->tap(fn($q) => $this->pidFilter($q))->where('type','damaged')->count(),
            'isolated' => IsolatedItem::query()->tap(fn($q) => $this->pidFilter($q))->where('status','isolated')->count(),
            'scrapped' => IsolatedItem::query()->tap(fn($q) => $this->pidFilter($q))->where('status','scrapped')->count(),
            'repaired' => IsolatedItem::query()->tap(fn($q) => $this->pidFilter($q))->where('status','repaired')->count(),
            'qty'      => IsolatedItem::query()->tap(fn($q) => $this->pidFilter($q))->sum('quantity'),
        ];

        return view('isolated.index', compact('isolated', 'stats'));
    }

    public function create()
    {
        $items = Item::query()->tap(fn($q) => $this->pidFilter($q))->orderBy('name')->get();
        return view('isolated.create', compact('items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'quantity'      => 'required|integer|min:1',
            'type'          => 'required|in:defect,damaged',
            'isolated_date' => 'required|date',
            'status'        => 'required|in:isolated,scrapped,repaired',
            'part_number'   => 'nullable|string|max:100',
            'reason'        => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            IsolatedItem::create([
                'item_id'       => $request->item_id ?: null,
                'name'          => $request->name,
                'part_number'   => $request->part_number,
                'quantity'      => $request->quantity,
                'type'          => $request->type,
                'reason'        => $request->reason,
                'isolated_date' => $request->isolated_date,
                'status'        => $request->status,
                'notes'         => $request->notes,
                'project_id'    => session('current_project_id'),
            ]);

            // Deduct from item inventory if linked
            if ($request->item_id) {
                $item = Item::find($request->item_id);
                if ($item && $item->current_stock >= $request->quantity) {
                    $item->decrement('current_stock', $request->quantity);
                }
            }
        });

        return redirect()->route('isolated.index')
            ->with('success', "Isolated: {$request->quantity} × {$request->name} recorded.");
    }

    public function show(IsolatedItem $isolated)
    {
        $isolated->load('item');
        return view('isolated.show', compact('isolated'));
    }

    public function edit(IsolatedItem $isolated)
    {
        $items = Item::query()->tap(fn($q) => $this->pidFilter($q))->orderBy('name')->get();
        return view('isolated.edit', compact('isolated', 'items'));
    }

    public function update(Request $request, IsolatedItem $isolated)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'quantity'      => 'required|integer|min:1',
            'type'          => 'required|in:defect,damaged',
            'isolated_date' => 'required|date',
            'status'        => 'required|in:isolated,scrapped,repaired',
            'part_number'   => 'nullable|string|max:100',
            'reason'        => 'nullable|string',
            'proof_images'  => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $isolated) {
            $oldQty    = $isolated->quantity;
            $oldItemId = $isolated->item_id;
            $newQty    = (int)$request->quantity;
            $newItemId = $request->item_id ?: null;

            // Restore old deduction if item was linked
            if ($oldItemId) {
                $oldItem = Item::find($oldItemId);
                if ($oldItem) $oldItem->increment('current_stock', $oldQty);
            }

            // Handle proof images — keep existing if no new ones uploaded
            $proofImages = $isolated->proof_images ?? [];

            // Merge incoming images — filter empty strings and keep only valid base64
            $rawImages = $request->input('proof_images', []);
            if (!is_array($rawImages)) $rawImages = [];
            $incoming = array_values(array_filter($rawImages, fn($v) => !empty(trim($v))));
            if (count($incoming) > 0) {
                $proofImages = $incoming;
            }

            // Handle individual slot deletions
            if ($request->has('delete_proof')) {
                foreach ($request->delete_proof as $idx) {
                    unset($proofImages[(int)$idx]);
                }
                $proofImages = array_values($proofImages);
            }

            $isolated->update([
                'item_id'       => $newItemId,
                'name'          => $request->name,
                'part_number'   => $request->part_number,
                'quantity'      => $newQty,
                'type'          => $request->type,
                'reason'        => $request->reason,
                'isolated_date' => $request->isolated_date,
                'status'        => $request->status,
                'notes'         => $request->notes,
                'proof_images'  => count($proofImages) > 0 ? $proofImages : null,
            ]);

            // Apply new deduction
            if ($newItemId) {
                $newItem = Item::find($newItemId);
                if ($newItem && $newItem->current_stock >= $newQty) {
                    $newItem->decrement('current_stock', $newQty);
                }
            }
        });

        return redirect()->route('isolated.index')
            ->with('success', 'Isolated item updated.');
    }

    public function destroy(IsolatedItem $isolated)
    {
        DB::transaction(function () use ($isolated) {
            // Restore stock if linked item exists and status is still isolated
            if ($isolated->item_id && $isolated->status === 'isolated') {
                $item = Item::find($isolated->item_id);
                if ($item) $item->increment('current_stock', $isolated->quantity);
            }
            $isolated->delete();
        });

        return redirect()->route('isolated.index')
            ->with('success', 'Isolated item record deleted.');
    }

    public function deleteProof(IsolatedItem $isolated, int $index)
    {
        $images = array_filter($isolated->proof_images ?? []);

        if (count($images) <= 1) {
            return back()->with('error', 'Cannot remove the last proof image.');
        }

        if (isset($images[$index])) {
            unset($images[$index]);
            $isolated->proof_images = array_values($images);
            $isolated->save();
        }

        return back()->with('success', 'Photo removed.');
    }
}
