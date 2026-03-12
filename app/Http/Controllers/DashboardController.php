<?php
namespace App\Http\Controllers;
use App\Models\Item;
use App\Models\Zone;
use App\Models\Container;
use App\Models\StockTransaction;

class DashboardController extends Controller
{
    public function index()
    {
        $pid = session('current_project_id');

        $totalItems     = Item::where('project_id', $pid)->count();
        $totalZones     = Zone::where('project_id', $pid)->count();
        $totalContainers = Container::where('project_id', $pid)->count();
        $lowStockItems  = Item::where('project_id', $pid)->whereColumn('current_stock', '<=', 'min_stock')->count();
        $lowStockList   = Item::where('project_id', $pid)->whereColumn('current_stock', '<=', 'min_stock')->orderBy('current_stock')->take(5)->get();
        $recentTransactions = StockTransaction::with(['item','zone'])
            ->whereHas('item', fn($q) => $q->where('project_id', $pid))
            ->latest()->take(10)->get();
        $allItems = Item::where('project_id', $pid)->orderBy('name')->get();

        return view('dashboard', compact(
            'totalItems','totalZones','totalContainers',
            'lowStockItems','lowStockList','recentTransactions','allItems'
        ));
    }
}
