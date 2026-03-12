<?php
namespace App\Http\Controllers;

use App\Exports\ContainerExport;
use App\Models\Container;
use App\Models\ContainerItem;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ContainerController extends Controller
{
    use HasProjectScope;

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Container::query()->tap(fn($q) => $this->pidFilter($q))->with('items.item');

        if ($request->search) {
            $query->where(fn($q) =>
                $q->where('container_id', 'like', '%'.$request->search.'%')
                  ->orWhere('description', 'like', '%'.$request->search.'%')
                  ->orWhere('batch',       'like', '%'.$request->search.'%')
                  ->orWhereHas('items.item', fn($qi) =>
                      $qi->where('name', 'like', '%'.$request->search.'%'))
            );
        }
        if ($request->status)    $query->where('status', $request->status);
        if ($request->batch)     $query->where('batch', 'like', '%'.$request->batch.'%');
        if ($request->item_name) $query->whereHas('items.item', fn($q) =>
            $q->where('name', 'like', '%'.$request->item_name.'%'));
        if ($request->date_from) $query->whereDate('date_in', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('date_in', '<=', $request->date_to);

        $containers = $query->latest()->paginate(15)->withQueryString();
        $batches    = Container::whereNotNull('batch')->distinct()->orderBy('batch')->pluck('batch');

        return view('containers.index', compact('containers', 'batches'));
    }

    // ── Export ────────────────────────────────────────────────────
    public function export(Request $request)
    {
        if (!$this->pid()) return back()->with('error', 'No project selected.');
        return Excel::download(
            new ContainerExport(
                $request->search, $request->status, $request->batch,
                $request->item_name, $request->date_from, $request->date_to,
                $this->pid(),
            ),
            'Containers_'.now()->format('Y-m-d').'.xlsx'
        );
    }

    // ── Create ────────────────────────────────────────────────────
    public function create()
    {
        $items   = Item::where('project_id', $this->pid())->orderBy('name')->get();
        $batches = Container::whereNotNull('batch')->distinct()->orderBy('batch')->pluck('batch');
        return view('containers.create', compact('items', 'batches'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'container_id' => 'required|unique:containers',
            'date_in'      => 'required|date',
            'date_out'     => 'nullable|date|after_or_equal:date_in',
        ]);

        $stockLog = [];

        DB::transaction(function () use ($request, &$stockLog) {
            $container = Container::create(array_merge($request->only([
                'container_id', 'batch', 'description', 'date_in', 'date_out', 'status', 'color_code',
            ]), ['project_id' => $this->pid()]));

            foreach ((array)$request->items as $row) {
                if (empty($row['item_id'])) continue;

                ContainerItem::create([
                    'container_id' => $container->id,
                    'item_id'      => $row['item_id'],
                    'part_number'  => $row['part_number'] ?? null,
                    'description'  => $row['description'] ?? null,
                    'quantity'     => $row['quantity'] ?? 1,
                ]);

                // Container arriving → increase item stock
                $item = Item::find($row['item_id']);
                if ($item) {
                    $qty = max(1, (int)($row['quantity'] ?? 1));
                    $item->increment('current_stock', $qty);
                    $stockLog[] = "+{$qty} × {$item->name} (now {$item->fresh()->current_stock})";
                }
            }
        });

        $msg = 'Container created!';
        if ($stockLog) {
            $msg .= ' 📦 Stock increased: '.implode(', ', $stockLog).'.';
        }
        return redirect()->route('containers.index')->with('success', $msg);
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(Container $container)
    {
        $container->load('items.item');
        return view('containers.show', compact('container'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(Container $container)
    {
        $items   = Item::where('project_id', $this->pid())->orderBy('name')->get();
        $batches = Container::whereNotNull('batch')->distinct()->orderBy('batch')->pluck('batch');
        $container->load('items.item');
        return view('containers.edit', compact('container', 'items', 'batches'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, Container $container)
    {
        $request->validate([
            'container_id' => 'required|unique:containers,container_id,'.$container->id,
            'date_in'      => 'required|date',
            'date_out'     => 'nullable|date|after_or_equal:date_in',
        ]);

        $container->update($request->only([
            'container_id', 'batch', 'description', 'date_in', 'date_out', 'status', 'color_code',
        ]));

        return redirect()->route('containers.show', $container)
                         ->with('success', 'Container updated!');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(Container $container)
    {
        $stockLog = [];

        DB::transaction(function () use ($container, &$stockLog) {
            // Reverse stock that was added when this container arrived
            $container->load('items.item');
            foreach ($container->items as $ci) {
                if ($ci->item) {
                    $qty  = (int)$ci->quantity;
                    $safe = min($qty, $ci->item->current_stock);
                    if ($safe > 0) {
                        $ci->item->decrement('current_stock', $safe);
                        $stockLog[] = "-{$safe} × {$ci->item->name}";
                    }
                }
            }
            $container->items()->delete();
            $container->delete();
        });

        $msg = 'Container deleted.';
        if ($stockLog) {
            $msg .= ' 📦 Stock reversed: '.implode(', ', $stockLog).'.';
        }
        return redirect()->route('containers.index')->with('success', $msg);
    }
}
