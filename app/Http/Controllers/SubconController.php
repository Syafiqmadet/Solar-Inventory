<?php
namespace App\Http\Controllers;

use App\Models\Subcon;
use App\Models\SubconMif;
use App\Models\SubconMifItem;
use App\Models\SubconMrf;
use App\Models\SubconMrfItem;
use App\Models\Zone;
use App\Models\Item;
use App\Models\IsolatedItem;
use App\Models\StockTransaction;
use App\Exports\SubconMifExport;
use App\Exports\SubconMrfExport;
use App\Exports\SubconMaterialReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SubconController extends Controller
{
    use HasProjectScope;
    // ── Subcon CRUD ────────────────────────────────────────────────

    public function index()
    {
        $subcons = Subcon::with('zone')
            ->where('project_id', $this->pid())
            ->orderByDesc('created_at')->get();
        $zones = Zone::where('project_id', $this->pid())->orderBy('name')->get();
        return view('subcon.index', compact('subcons','zones'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'contact_person'     => 'nullable|string|max:255',
            'contact_number'     => 'nullable|string|max:50',
            'supervisor_name'    => 'nullable|string|max:255',
            'supervisor_contact' => 'nullable|string|max:50',
            'zone_id'            => 'nullable|exists:zones,id',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date|after_or_equal:start_date',
            'status'             => 'required|in:active,completed,terminated',
            'notes'              => 'nullable|string',
        ]);
        $data['project_id'] = $this->pid();
        Subcon::create($data);
        return back()->with('success', 'Subcontractor added successfully.');
    }

    public function update(Request $request, Subcon $subcon)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'contact_person'     => 'nullable|string|max:255',
            'contact_number'     => 'nullable|string|max:50',
            'supervisor_name'    => 'nullable|string|max:255',
            'supervisor_contact' => 'nullable|string|max:50',
            'zone_id'            => 'nullable|exists:zones,id',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date',
            'status'             => 'required|in:active,completed,terminated',
            'notes'              => 'nullable|string',
        ]);
        $subcon->update($data);
        return back()->with('success', 'Subcontractor updated.');
    }

    public function destroy(Subcon $subcon)
    {
        $subcon->delete();
        return back()->with('success', 'Subcontractor deleted.');
    }

    // ── MIF ────────────────────────────────────────────────────────

    public function mifIndex(Subcon $subcon)
    {
        $mifs  = $subcon->mifs()->with('items','issuedBy')->orderByDesc('date')->get();
        $items = Item::where('project_id', $this->pid())->where('current_stock', '>', 0)->orderBy('name')->get();
        return view('subcon.mif', compact('subcon','mifs','items'));
    }

    public function mifStore(Request $request, Subcon $subcon)
    {
        $request->validate([
            'mif_number'            => ['required','string','max:100',
                'unique:subcon_mif,mif_number',
                'unique:subcon_mrf,mrf_number',
            ],
            'date'                  => 'required|date',
            'notes'                 => 'nullable|string',
            'rows'                  => 'required|array|min:1',
            'rows.*.title'          => 'nullable|string|max:255',
            'rows.*.item_id'        => 'nullable|exists:items,id',
            'rows.*.item_name'      => 'required|string',
            'rows.*.quantity'       => 'required|integer|min:1',
            'rows.*.unit'           => 'nullable|string|max:50',
            'rows.*.remarks'        => 'nullable|string',
        ], [
            'mif_number.unique' => 'This MIF number already exists. Each MIF and MRF number must be unique.',
        ]);

        // Validate stock availability before transaction
        foreach ($request->rows as $row) {
            if (!empty($row['item_id'])) {
                $item = Item::find($row['item_id']);
                if ($item && $row['quantity'] > $item->current_stock) {
                    return back()->withInput()->withErrors([
                        'rows' => 'Item "' . $row['item_name'] . '" — requested qty (' . $row['quantity'] . ') exceeds available stock (' . $item->current_stock . ' ' . ($item->unit ?? 'units') . ').'
                    ]);
                }
            }
        }

        DB::transaction(function () use ($request, $subcon) {
            $mif = SubconMif::create([
                'mif_number' => $request->mif_number,
                'subcon_id'  => $subcon->id,
                'zone_id'    => $subcon->zone_id,
                'project_id' => $this->pid(),
                'issued_by'  => Auth::id(),
                'date'       => $request->date,
                'notes'      => $request->notes,
            ]);

            foreach ($request->rows as $row) {
                SubconMifItem::create([
                    'mif_id'      => $mif->id,
                    'title'       => $row['title'] ?? null,
                    'item_id'     => $row['item_id'] ?? null,
                    'item_name'   => $row['item_name'],
                    'part_number' => $row['part_number'] ?? null,
                    'quantity'    => $row['quantity'],
                    'unit'        => $row['unit'] ?? null,
                    'remarks'     => $row['remarks'] ?? null,
                ]);

                if (!empty($row['item_id'])) {
                    $item = Item::find($row['item_id']);
                    if ($item) {
                        $item->decrement('current_stock', $row['quantity']);

                        // Record zone stock OUT (deployed to zone via subcon)
                        if ($subcon->zone_id) {
                            StockTransaction::create([
                                'item_id'  => $item->id,
                                'zone_id'  => $subcon->zone_id,
                                'type'     => 'in',
                                'quantity' => $row['quantity'],
                                'notes'    => 'MIF ' . $mif->mif_number . ' — Issued to subcon: ' . $subcon->name,
                            ]);
                        }
                    }
                }
            }
        });

        return back()->with('success', 'MIF recorded and stock updated.');
    }

    public function mifDestroy(Subcon $subcon, SubconMif $mif)
    {
        DB::transaction(function () use ($mif) {
            foreach ($mif->items as $row) {
                if ($row->item_id) {
                    $item = Item::find($row->item_id);
                    if ($item) {
                        $item->increment('current_stock', $row->quantity);

                        // Reverse zone stock transaction
                        if ($mif->zone_id) {
                            StockTransaction::create([
                                'item_id'  => $item->id,
                                'zone_id'  => $mif->zone_id,
                                'type'     => 'out',
                                'quantity' => $row->quantity,
                                'notes'    => 'MIF ' . $mif->mif_number . ' deleted — stock returned from zone',
                            ]);
                        }
                    }
                }
            }
            $mif->delete();
        });
        return back()->with('success', 'MIF deleted and stock restored.');
    }

    public function mifExport(Subcon $subcon)
    {
        $mifs = $subcon->mifs()->with('items','issuedBy')->orderByDesc('date')->get();
        $filename = 'MIF_' . str_replace(' ', '_', $subcon->name) . '_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new SubconMifExport($subcon, $mifs), $filename);
    }

    public function materialReport()
    {
        $pid = $this->pid();
        if (!$pid) return back()->with('error', 'No project selected.');
        $filename = 'Material_Usage_Report_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new SubconMaterialReportExport($pid), $filename);
    }

    // ── MRF ────────────────────────────────────────────────────────

    public function mrfIndex(Subcon $subcon)
    {
        $mrfs = $subcon->mrfs()->with('items')->orderByDesc('date')->get();

        // Only items issued via MIF to this subcon, with remaining returnable qty
        $mifItems = SubconMifItem::whereHas('mif', fn($q) => $q->where('subcon_id', $subcon->id))
            ->whereNotNull('item_id')
            ->with('item')
            ->get()
            ->groupBy('item_id')
            ->map(function ($rows) use ($subcon) {
                $first      = $rows->first();
                $issuedQty  = $rows->sum('quantity');

                // Subtract qty already returned via MRF for this subcon
                $returnedQty = SubconMrfItem::whereHas('mrf', fn($q) => $q->where('subcon_id', $subcon->id))
                    ->where('item_id', $first->item_id)
                    ->sum('quantity');

                return (object) [
                    'id'          => $first->item_id,
                    'name'        => $first->item_name,
                    'part_number' => $first->part_number,
                    'unit'        => $first->unit ?? $first->item?->unit,
                    'issued_qty'  => $issuedQty,
                    'returned_qty'=> $returnedQty,
                    'remaining'   => max(0, $issuedQty - $returnedQty),
                ];
            })
            ->filter(fn($i) => $i->remaining > 0) // only show items with qty still in zone
            ->values();

        $mifItems = $mifItems ?? collect();

        return view('subcon.mrf', compact('subcon', 'mrfs', 'mifItems'));
    }

    public function mrfStore(Request $request, Subcon $subcon)
    {
        $request->validate([
            'mrf_number'            => ['required','string','max:100',
                'unique:subcon_mrf,mrf_number',
                'unique:subcon_mif,mif_number',
            ],
            'date'                  => 'required|date',
            'notes'                 => 'nullable|string',
            'rows'                  => 'required|array|min:1',
            'rows.*.title'          => 'nullable|string|max:255',
            'rows.*.item_id'        => 'nullable|exists:items,id',
            'rows.*.item_name'      => 'required|string',
            'rows.*.quantity'       => 'required|integer|min:1',
            'rows.*.unit'           => 'nullable|string|max:50',
            'rows.*.condition'      => 'required|in:good,damaged,defect',
            'rows.*.remarks'        => 'nullable|string',
        ], [
            'mrf_number.unique' => 'This MRF number already exists. Each MIF and MRF number must be unique.',
        ]);

        // Validate each item qty does not exceed what was issued via MIF
        foreach ($request->rows as $idx => $row) {
            if (empty($row['item_id'])) continue;
            $issuedQty   = SubconMifItem::whereHas('mif', fn($q) => $q->where('subcon_id', $subcon->id))
                ->where('item_id', $row['item_id'])->sum('quantity');
            $returnedQty = SubconMrfItem::whereHas('mrf', fn($q) => $q->where('subcon_id', $subcon->id))
                ->where('item_id', $row['item_id'])->sum('quantity');
            $remaining = $issuedQty - $returnedQty;
            if ($row['quantity'] > $remaining) {
                return back()->withInput()->with('error',
                    'Item "' . $row['item_name'] . '" — return qty (' . $row['quantity'] . ') exceeds available in zone (' . $remaining . ').');
            }
        }

        DB::transaction(function () use ($request, $subcon) {
            $mrf = SubconMrf::create([
                'mrf_number' => $request->mrf_number,
                'subcon_id'  => $subcon->id,
                'zone_id'    => $subcon->zone_id,
                'project_id' => $this->pid(),
                'date'       => $request->date,
                'notes'      => $request->notes,
            ]);

            foreach ($request->rows as $row) {
                SubconMrfItem::create([
                    'mrf_id'      => $mrf->id,
                    'title'       => $row['title'] ?? null,
                    'item_id'     => $row['item_id'] ?? null,
                    'item_name'   => $row['item_name'],
                    'part_number' => $row['part_number'] ?? null,
                    'quantity'    => $row['quantity'],
                    'unit'        => $row['unit'] ?? null,
                    'condition'   => $row['condition'],
                    'remarks'     => $row['remarks'] ?? null,
                ]);

                if ($row['condition'] === 'good' && !empty($row['item_id'])) {
                    $item = Item::find($row['item_id']);
                    if ($item) {
                        $item->increment('current_stock', $row['quantity']);

                        // Zone stock OUT — item returned from zone to warehouse
                        if ($subcon->zone_id) {
                            StockTransaction::create([
                                'item_id'  => $item->id,
                                'zone_id'  => $subcon->zone_id,
                                'type'     => 'out',
                                'quantity' => $row['quantity'],
                                'notes'    => 'MRF ' . $mrf->mrf_number . ' — Good condition, returned to stock from subcon: ' . $subcon->name,
                            ]);
                        }
                    }
                } elseif (in_array($row['condition'], ['damaged', 'defect'])) {
                    $isolateType   = $row['condition']; // 'damaged' or 'defect'
                    $isolateReason = 'Returned via MRF ' . $mrf->mrf_number . ' (' . ucfirst($isolateType) . ') from subcon ' . $subcon->name . ($row['remarks'] ? ' | ' . $row['remarks'] : '');

                    IsolatedItem::create([
                        'item_id'       => $row['item_id'] ?? null,
                        'name'          => $row['item_name'],
                        'part_number'   => $row['part_number'] ?? null,
                        'quantity'      => $row['quantity'],
                        'type'          => $isolateType,
                        'status'        => 'isolated',
                        'reason'        => $isolateReason,
                        'notes'         => $row['remarks'] ?? null,
                        'isolated_date' => $request->date,
                        'project_id'    => $this->pid(),
                    ]);

                    // Zone stock OUT — item leaves zone to isolated
                    if ($subcon->zone_id && !empty($row['item_id'])) {
                        $item = Item::find($row['item_id']);
                        if ($item) {
                            StockTransaction::create([
                                'item_id'  => $item->id,
                                'zone_id'  => $subcon->zone_id,
                                'type'     => 'out',
                                'quantity' => $row['quantity'],
                                'notes'    => 'MRF ' . $mrf->mrf_number . ' — ' . ucfirst($isolateType) . ', sent to isolated items from subcon: ' . $subcon->name,
                            ]);
                        }
                    }
                }
            }
        });

        return back()->with('success', 'MRF recorded. Good items returned to stock; damaged items sent to isolated.');
    }

    public function mrfDestroy(Subcon $subcon, SubconMrf $mrf)
    {
        DB::transaction(function () use ($mrf) {
            foreach ($mrf->items as $row) {
                if ($row->item_id) {
                    $item = Item::find($row->item_id);
                    if ($item && $row->condition === 'good') {
                        $item->decrement('current_stock', $row->quantity);

                        // Reverse zone stock OUT — item goes back to zone
                        if ($mrf->zone_id) {
                            StockTransaction::create([
                                'item_id'  => $item->id,
                                'zone_id'  => $mrf->zone_id,
                                'type'     => 'in',
                                'quantity' => $row->quantity,
                                'notes'    => 'MRF ' . $mrf->mrf_number . ' deleted — good item re-deployed to zone',
                            ]);
                        }
                    } elseif ($item && in_array($row->condition, ['damaged', 'defect']) && $mrf->zone_id) {
                        // Reverse zone stock OUT for damaged — item goes back to zone
                        StockTransaction::create([
                            'item_id'  => $item->id,
                            'zone_id'  => $mrf->zone_id,
                            'type'     => 'in',
                            'quantity' => $row->quantity,
                            'notes'    => 'MRF ' . $mrf->mrf_number . ' deleted — damaged item re-deployed to zone',
                        ]);
                    }
                }
            }
            $mrf->items()->whereNotNull('item_id')->each(function ($row) use ($mrf) {
                IsolatedItem::where('reason', 'like', '%MRF ' . $mrf->mrf_number . '%')
                    ->where('item_id', $row->item_id)
                    ->delete();
            });
            $mrf->delete();
        });
        return back()->with('success', 'MRF deleted.');
    }

    public function checkNumber(Request $request)
    {
        $number  = trim($request->number ?? '');
        $exclude = $request->exclude ?? null; // current record id to ignore on edit

        $inMif = \DB::table('subcon_mif')->where('mif_number', $number)
            ->when($exclude, fn($q) => $q->where('id', '!=', $exclude))
            ->exists();

        $inMrf = \DB::table('subcon_mrf')->where('mrf_number', $number)
            ->when($exclude, fn($q) => $q->where('id', '!=', $exclude))
            ->exists();

        return response()->json([
            'taken'  => $inMif || $inMrf,
            'in_mif' => $inMif,
            'in_mrf' => $inMrf,
        ]);
    }

    public function mrfExport(Subcon $subcon)
    {
        $mrfs = $subcon->mrfs()->with('items')->orderByDesc('date')->get();
        $filename = 'MRF_' . str_replace(' ', '_', $subcon->name) . '_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new SubconMrfExport($subcon, $mrfs), $filename);
    }

}
