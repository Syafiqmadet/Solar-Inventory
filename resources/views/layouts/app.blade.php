<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>☀️ Solar Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --solar-orange:#FF6B35; --solar-yellow:#FFD700;
            --solar-dark:#1a1a2e;  --solar-mid:#16213e; --solar-blue:#0f3460;
            --sb-wide: 240px; --sb-narrow: 64px;
        }
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }

        /* ── Sidebar ───────────────────────────────────────────── */
        .sidebar {
            width: var(--sb-wide);
            min-height: 100vh;
            background: linear-gradient(180deg, var(--solar-dark) 0%, var(--solar-mid) 60%, var(--solar-blue) 100%);
            position: fixed; top:0; left:0; z-index:200;
            box-shadow: 4px 0 20px rgba(0,0,0,0.3);
            overflow: hidden;
            display: flex; flex-direction: column;
            transition: width 0.25s ease;
        }
        .sidebar.collapsed { width: var(--sb-narrow); }
        .sidebar::-webkit-scrollbar { width: 3px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius:4px; }

        /* ── Brand ─────────────────────────────────────────────── */
        .sidebar-brand {
            background: linear-gradient(135deg, var(--solar-orange), var(--solar-yellow));
            padding: 14px 12px; text-align: center; flex-shrink: 0;
            display: flex; align-items: center; justify-content: space-between;
            min-height: 64px;
        }
        .sidebar-brand .brand-info { display:flex; align-items:center; gap:10px; overflow:hidden; }
        .sidebar-brand .sun-icon { font-size:1.6rem; flex-shrink:0; line-height:1; }
        .sidebar-brand .brand-text { text-align:left; white-space:nowrap; overflow:hidden; transition: opacity 0.2s, width 0.25s; }
        .sidebar-brand .brand-text h5 { color:#fff; font-weight:800; margin:0; font-size:0.95rem; }
        .sidebar-brand .brand-text small { color:rgba(255,255,255,0.85); font-size:0.7rem; }
        .sidebar.collapsed .brand-text { opacity:0; width:0; }

        /* Toggle button */
        .sb-toggle {
            background: rgba(255,255,255,0.2); border: none; border-radius: 6px;
            color:#fff; width:28px; height:28px; flex-shrink:0;
            display:flex; align-items:center; justify-content:center;
            cursor:pointer; transition: background 0.15s;
            font-size: 0.85rem;
        }
        .sb-toggle:hover { background: rgba(255,255,255,0.35); }

        /* ── Nav scroll area ───────────────────────────────────── */
        .sb-nav { flex:1; overflow-y:auto; overflow-x:hidden; padding-bottom:12px; }
        .sb-nav::-webkit-scrollbar { width:3px; }
        .sb-nav::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.12); border-radius:4px; }

        /* ── Dashboard link ────────────────────────────────────── */
        .sidebar-dashboard {
            display:flex; align-items:center; gap:10px;
            padding: 11px 20px; color:rgba(255,255,255,0.8);
            font-size:0.875rem; font-weight:600; text-decoration:none;
            border-left:3px solid transparent; white-space:nowrap;
            transition: all 0.15s; position:relative;
        }
        .sidebar-dashboard i { font-size:1.05rem; width:22px; flex-shrink:0; text-align:center; }
        .sidebar-dashboard span { transition: opacity 0.2s; }
        .sidebar-dashboard:hover, .sidebar-dashboard.active {
            color:#fff; background:rgba(255,255,255,0.1);
            border-left-color:var(--solar-orange);
        }
        .sidebar.collapsed .sidebar-dashboard { padding:11px; justify-content:center; border-left-color:transparent !important; }
        .sidebar.collapsed .sidebar-dashboard.active { background:rgba(255,255,255,0.15); }
        .sidebar.collapsed .sidebar-dashboard span { opacity:0; width:0; overflow:hidden; }

        /* ── Group header ──────────────────────────────────────── */
        .sb-group { border-bottom:1px solid rgba(255,255,255,0.05); }

        .sb-group-header {
            display:flex; align-items:center; justify-content:space-between;
            padding:10px 20px; cursor:pointer; white-space:nowrap;
            color:rgba(255,255,255,0.5); font-size:0.72rem;
            font-weight:700; text-transform:uppercase; letter-spacing:0.7px;
            user-select:none; transition:color 0.15s, background 0.15s;
        }
        .sb-group-header:hover { color:rgba(255,255,255,0.85); background:rgba(255,255,255,0.04); }
        .sb-group-header.open, .sb-group-header.has-active { color:rgba(255,255,255,0.9); }

        .sb-group-left { display:flex; align-items:center; gap:8px; }
        .sb-group-left i { font-size:1rem; width:22px; text-align:center; flex-shrink:0; }
        .sb-group-label { transition:opacity 0.2s; }

        .sb-chevron { font-size:0.65rem; transition:transform 0.22s ease, opacity 0.2s; color:rgba(255,255,255,0.3); }
        .sb-group-header.open .sb-chevron { transform:rotate(90deg); color:rgba(255,255,255,0.6); }

        /* Collapsed: hide labels + chevron, center icon */
        .sidebar.collapsed .sb-group-header { padding:11px; justify-content:center; }
        .sidebar.collapsed .sb-group-label  { opacity:0; width:0; overflow:hidden; }
        .sidebar.collapsed .sb-chevron      { opacity:0; width:0; overflow:hidden; }

        /* ── Sub-links ─────────────────────────────────────────── */
        .sb-links { overflow:hidden; transition:max-height 0.28s ease; max-height:0; }
        .sb-links.open { max-height:400px; }
        .sidebar.collapsed .sb-links { max-height:0 !important; }

        .sb-link {
            display:flex; align-items:center; gap:9px;
            padding:8px 20px 8px 34px; color:rgba(255,255,255,0.65);
            font-size:0.845rem; text-decoration:none; white-space:nowrap;
            border-left:3px solid transparent; transition:all 0.15s;
        }
        .sb-link i { font-size:0.9rem; width:16px; flex-shrink:0; opacity:0.85; }
        .sb-link span { transition:opacity 0.2s; }
        .sb-link:hover { color:#fff; background:rgba(255,255,255,0.08); border-left-color:rgba(255,255,255,0.3); }
        .sb-link.active { color:#fff; background:rgba(255,255,255,0.12); font-weight:600; }

        .sb-link.accent-orange.active, .sb-link.accent-orange:hover { border-left-color:var(--solar-orange); }
        .sb-link.accent-green.active,  .sb-link.accent-green:hover  { border-left-color:#28a745; }
        .sb-link.accent-blue.active,   .sb-link.accent-blue:hover   { border-left-color:#0d6efd; }
        .sb-link.accent-red.active,    .sb-link.accent-red:hover    { border-left-color:#dc3545; }
        .sb-link.accent-teal.active,   .sb-link.accent-teal:hover   { border-left-color:#0dcaf0; }

        /* ── Tooltip on collapsed ──────────────────────────────── */
        .sb-tip {
            position:absolute; left:calc(var(--sb-narrow) + 8px); top:50%; transform:translateY(-50%);
            background:#1a1a2e; color:#fff; font-size:0.78rem; font-weight:600;
            padding:4px 10px; border-radius:6px; white-space:nowrap;
            pointer-events:none; opacity:0; transition:opacity 0.15s;
            z-index:300; box-shadow:0 2px 8px rgba(0,0,0,0.3);
        }
        .sidebar.collapsed .sidebar-dashboard:hover .sb-tip,
        .sidebar.collapsed .sb-group-header:hover .sb-tip { opacity:1; }

        /* ── Main content ──────────────────────────────────────── */
        .main-content { margin-left:var(--sb-wide); min-height:100vh; transition:margin-left 0.25s ease; }
        .main-content.collapsed { margin-left:var(--sb-narrow); }

        .topbar { background:#fff; padding:11px 24px; border-bottom:1px solid #e0e0e0; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .page-header { background:linear-gradient(135deg,var(--solar-orange),var(--solar-yellow)); color:white; padding:22px 28px; }
        .page-header h1 { margin:0; font-size:1.5rem; font-weight:700; }
        .content-area { padding:24px; }
        .card { border:none; box-shadow:0 2px 12px rgba(0,0,0,0.08); border-radius:12px; }
        .card-header { border-radius:12px 12px 0 0 !important; font-weight:600; }
        .btn-solar { background:linear-gradient(135deg,var(--solar-orange),#ff8c42); color:white; border:none; }
        .btn-solar:hover { background:linear-gradient(135deg,#e55d28,var(--solar-orange)); color:white; }
        .table th { background:#f8f9fa; font-weight:600; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; }
        .table td { vertical-align:middle; }
        .color-dot { width:14px; height:14px; border-radius:50%; display:inline-block; border:2px solid rgba(0,0,0,0.1); }
    </style>
    @yield('styles')
</head>
<body>

@php
    $onInventory  = request()->routeIs('items.*') || request()->routeIs('isolated.*');
    $onZones      = request()->routeIs('zones.*') || request()->routeIs('subcon.*');
    $onContainers = request()->routeIs('containers.*');
    $onFuel       = request()->routeIs('fuel.*');
    $onVehicles   = request()->routeIs('vehicles.*');
    $onUsers      = request()->routeIs('users.*');
    $canWrite     = auth()->check() && auth()->user()->canWrite();
@endphp

<div class="sidebar" id="sidebar">

    {{-- Brand + toggle --}}
    <div class="sidebar-brand">
        <div class="brand-info">
            <span class="sun-icon">☀️</span>
            <div class="brand-text">
                <h5>Solar Inventory</h5>
                <small>Management System</small>
            </div>
        </div>
        <button class="sb-toggle" id="sidebarToggle" title="Toggle sidebar">
            <i class="bi bi-layout-sidebar-reverse" id="toggleIcon"></i>
        </button>
    </div>

    <div class="sb-nav">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="sidebar-dashboard {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           style="position:relative">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
            <span class="sb-tip">Dashboard</span>
        </a>

        {{-- ── Inventory ───────────────────────────────── --}}
        <div class="sb-group">
            <div class="sb-group-header {{ $onInventory ? 'open has-active' : '' }}"
                 data-target="sb-inventory" style="position:relative">
                <span class="sb-group-left">
                    <i class="bi bi-boxes"></i>
                    <span class="sb-group-label">Inventory</span>
                </span>
                <i class="bi bi-chevron-right sb-chevron"></i>
                <span class="sb-tip">Inventory</span>
            </div>
            <div class="sb-links {{ $onInventory ? 'open' : '' }}" id="sb-inventory">
                <a href="{{ route('items.index') }}" class="sb-link accent-orange {{ request()->routeIs('items.index','items.show','items.edit') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i><span>Items / Parts</span>
                </a>
                @if($canWrite)
                <a href="{{ route('items.create') }}" class="sb-link accent-orange {{ request()->routeIs('items.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle"></i><span>Add Item</span>
                </a>
                @endif
                <a href="{{ route('isolated.index') }}" class="sb-link accent-orange {{ request()->routeIs('isolated.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-exclamation"></i><span>Isolated Items</span>
                </a>
            </div>
        </div>

        {{-- ── Zones ───────────────────────────────────── --}}
        <div class="sb-group">
            <div class="sb-group-header {{ $onZones ? 'open has-active' : '' }}"
                 data-target="sb-zones" style="position:relative">
                <span class="sb-group-left">
                    <i class="bi bi-geo-alt"></i>
                    <span class="sb-group-label">Zones</span>
                </span>
                <i class="bi bi-chevron-right sb-chevron"></i>
                <span class="sb-tip">Zones</span>
            </div>
            <div class="sb-links {{ $onZones ? 'open' : '' }}" id="sb-zones">
                <a href="{{ route('zones.index') }}" class="sb-link accent-teal {{ request()->routeIs('zones.index','zones.show','zones.edit','zones.stock.form') ? 'active' : '' }}">
                    <i class="bi bi-map"></i><span>All Zones</span>
                </a>
                @if($canWrite)
                <a href="{{ route('zones.create') }}" class="sb-link accent-teal {{ request()->routeIs('zones.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle"></i><span>Add Zone</span>
                </a>
                <a href="{{ route('subcon.index') }}" class="sb-link accent-teal {{ request()->routeIs('subcon.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i><span>Subcon</span>
                </a>
                @endif
            </div>
        </div>

        {{-- ── Containers ───────────────────────────────── --}}
        <div class="sb-group">
            <div class="sb-group-header {{ $onContainers ? 'open has-active' : '' }}"
                 data-target="sb-containers" style="position:relative">
                <span class="sb-group-left">
                    <i class="bi bi-archive"></i>
                    <span class="sb-group-label">Containers</span>
                </span>
                <i class="bi bi-chevron-right sb-chevron"></i>
                <span class="sb-tip">Containers</span>
            </div>
            <div class="sb-links {{ $onContainers ? 'open' : '' }}" id="sb-containers">
                <a href="{{ route('containers.index') }}" class="sb-link accent-orange {{ request()->routeIs('containers.index','containers.show','containers.edit') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3-gap"></i><span>All Containers</span>
                </a>
                @if($canWrite)
                <a href="{{ route('containers.create') }}" class="sb-link accent-orange {{ request()->routeIs('containers.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle"></i><span>Add Container</span>
                </a>
                @endif
            </div>
        </div>

        {{-- ── Fuel ─────────────────────────────────────── --}}
        <div class="sb-group">
            <div class="sb-group-header {{ $onFuel ? 'open has-active' : '' }}"
                 data-target="sb-fuel" style="position:relative">
                <span class="sb-group-left">
                    <i class="bi bi-fuel-pump"></i>
                    <span class="sb-group-label">Fuel</span>
                </span>
                <i class="bi bi-chevron-right sb-chevron"></i>
                <span class="sb-tip">Fuel</span>
            </div>
            <div class="sb-links {{ $onFuel ? 'open' : '' }}" id="sb-fuel">
                <a href="{{ route('fuel.index') }}" class="sb-link accent-green {{ request()->routeIs('fuel.index','fuel.show','fuel.edit') ? 'active' : '' }}">
                    <i class="bi bi-list-ul"></i><span>Fuel Records</span>
                </a>
                @if($canWrite)
                <a href="{{ route('fuel.create') }}" class="sb-link accent-green {{ request()->routeIs('fuel.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle"></i><span>Add Record</span>
                </a>
                @endif
            </div>
        </div>

        {{-- ── Vehicles ─────────────────────────────────── --}}
        <div class="sb-group">
            <div class="sb-group-header {{ $onVehicles ? 'open has-active' : '' }}"
                 data-target="sb-vehicles" style="position:relative">
                <span class="sb-group-left">
                    <i class="bi bi-truck"></i>
                    <span class="sb-group-label">Vehicles</span>
                </span>
                <i class="bi bi-chevron-right sb-chevron"></i>
                <span class="sb-tip">Vehicles</span>
            </div>
            <div class="sb-links {{ $onVehicles ? 'open' : '' }}" id="sb-vehicles">
                <a href="{{ route('vehicles.index') }}" class="sb-link accent-blue {{ request()->routeIs('vehicles.index','vehicles.show') ? 'active' : '' }}">
                    <i class="bi bi-card-list"></i><span>All Vehicles</span>
                </a>
                @if($canWrite)
                <a href="{{ route('vehicles.create') }}" class="sb-link accent-blue {{ request()->routeIs('vehicles.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle"></i><span>Add Vehicle</span>
                </a>
                @endif
            </div>
        </div>

        {{-- ── Admin ────────────────────────────────────── --}}
        @if(auth()->user()?->isAdmin())
        <div class="sb-group">
            <div class="sb-group-header {{ $onUsers || request()->routeIs('projects.*') ? 'open has-active' : '' }}"
                 data-target="sb-admin" style="position:relative">
                <span class="sb-group-left">
                    <i class="bi bi-shield-lock"></i>
                    <span class="sb-group-label">Admin</span>
                </span>
                <i class="bi bi-chevron-right sb-chevron"></i>
                <span class="sb-tip">Admin</span>
            </div>
            <div class="sb-links {{ $onUsers || request()->routeIs('projects.*') ? 'open' : '' }}" id="sb-admin">
                <a href="{{ route('projects.index') }}" class="sb-link accent-red {{ request()->routeIs('projects.index','projects.create','projects.edit') ? 'active' : '' }}">
                    <i class="bi bi-folder2-open"></i><span>Projects</span>
                </a>
                <a href="{{ route('users.index') }}" class="sb-link accent-red {{ request()->routeIs('users.index','users.edit') ? 'active' : '' }}">
                    <i class="bi bi-people"></i><span>Manage Users</span>
                </a>
                <a href="{{ route('users.create') }}" class="sb-link accent-red {{ request()->routeIs('users.create') ? 'active' : '' }}">
                    <i class="bi bi-person-plus"></i><span>Add User</span>
                </a>
            </div>
        </div>
        @endif

    </div>{{-- /sb-nav --}}
</div>

<div class="main-content" id="mainContent">
    <div class="topbar d-flex justify-content-between align-items-center">
        {{-- Current project badge --}}
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-sun-fill text-warning me-1"></i>
            @if($currentProject)
            <span class="fw-bold small" style="color:{{ $currentProject->color }}">
                🏗️ {{ $currentProject->name }}
            </span>
            <span class="text-muted small" style="font-size:0.75rem">({{ $currentProject->code }})</span>
            @else
            <span class="fw-semibold text-muted small">Solar Project Inventory</span>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i>{{ date('d M Y') }}</span>
            @auth
            {{-- Switch project button --}}
            <a href="{{ route('projects.switch') }}" class="btn btn-sm btn-outline-warning" style="font-size:0.78rem;padding:3px 10px" title="Switch Project">
                <i class="bi bi-arrow-left-right me-1"></i>Switch Project
            </a>
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                     style="width:32px;height:32px;background:var(--solar-orange);font-size:0.8rem;flex-shrink:0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span class="text-muted small fw-semibold">{{ auth()->user()->name }}</span>
                @if(auth()->user()->isViewer())
                <span class="badge" style="background:#6c757d;font-size:0.7rem">👁 View Only</span>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="mb-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:0.78rem;padding:3px 10px">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible m-3 mb-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible m-3 mb-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    const sidebar     = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleBtn   = document.getElementById('sidebarToggle');
    const toggleIcon  = document.getElementById('toggleIcon');
    const STORAGE_KEY = 'solarSidebarCollapsed';

    // Restore saved state
    if (localStorage.getItem(STORAGE_KEY) === '1') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('collapsed');
        toggleIcon.className = 'bi bi-layout-sidebar';
    }

    // Toggle on button click
    toggleBtn.addEventListener('click', function() {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed', isCollapsed);
        toggleIcon.className = isCollapsed ? 'bi bi-layout-sidebar' : 'bi bi-layout-sidebar-reverse';
        localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '0');
    });

    // Collapsible sub-groups (only when sidebar is wide)
    document.querySelectorAll('.sb-group-header').forEach(function(header) {
        header.addEventListener('click', function() {
            if (sidebar.classList.contains('collapsed')) return;
            const targetId = this.dataset.target;
            const links    = document.getElementById(targetId);
            const isOpen   = links.classList.contains('open');

            document.querySelectorAll('.sb-links').forEach(function(el) { el.classList.remove('open'); });
            document.querySelectorAll('.sb-group-header').forEach(function(el) { el.classList.remove('open'); });

            if (!isOpen) {
                links.classList.add('open');
                this.classList.add('open');
            }
        });
    });
})();
</script>
@yield('scripts')
@stack('scripts')
</body>
</html>
