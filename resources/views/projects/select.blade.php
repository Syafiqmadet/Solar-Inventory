<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Project — Solar Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --solar-orange:#FF6B35; --solar-dark:#1a1a2e; }
        body {
            background: linear-gradient(135deg, var(--solar-dark) 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .select-card {
            background: #fff; border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
            width: 100%; max-width: 680px; overflow: hidden;
        }
        .select-header {
            background: linear-gradient(135deg, var(--solar-orange), #FFD700);
            padding: 32px; text-align: center;
        }
        .select-header h2 { color:#fff; font-weight:800; margin:0; font-size:1.6rem; }
        .select-header p  { color:rgba(255,255,255,0.9); margin:6px 0 0; font-size:0.95rem; }
        .project-grid { padding: 28px; display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .project-card {
            border: 2px solid #e8e8e8; border-radius: 14px; padding: 20px;
            cursor: pointer; transition: all 0.2s; text-decoration: none; color: inherit;
            display: block; background: #fafafa;
        }
        .project-card:hover {
            border-color: var(--solar-orange); background: #fff7f4;
            box-shadow: 0 6px 20px rgba(255,107,53,0.15); transform: translateY(-2px); color: inherit;
        }
        .project-dot {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: 12px;
        }
        .project-name { font-weight: 700; font-size: 1rem; margin-bottom: 4px; }
        .project-code { font-size: 0.75rem; color: #888; font-family: monospace; }
        .project-meta { font-size: 0.8rem; color: #666; margin-top: 8px; }
        .no-projects { padding: 40px; text-align: center; color: #666; }
        .user-bar { background: #f8f9fa; padding: 14px 28px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
<div class="select-card">
    <div class="select-header">
        <div style="font-size:2.5rem;margin-bottom:8px">☀️</div>
        <h2>Select a Project</h2>
        <p>Choose the project you want to work on</p>
    </div>

    @if(session('error'))
    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
    @endif

    @if($projects->isEmpty())
    <div class="no-projects">
        <i class="bi bi-folder-x" style="font-size:3rem;color:#ccc"></i>
        <p class="mt-3 mb-1 fw-semibold">No projects assigned</p>
        <p class="small text-muted">Please contact your administrator to be assigned to a project.</p>
    </div>
    @else
    <div class="project-grid">
        @foreach($projects as $project)
        <form method="POST" action="{{ route('projects.choose') }}" style="margin:0">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">
            <button type="submit" class="project-card w-100 text-start border-0 p-0">
                <div style="padding:20px">
                    <div class="project-dot" style="background:{{ $project->color }}22">
                        <span style="font-size:1.4rem">🏗️</span>
                    </div>
                    <div class="project-name">{{ $project->name }}</div>
                    <div class="project-code">{{ $project->code }}</div>
                    @if($project->location)
                    <div class="project-meta"><i class="bi bi-geo-alt me-1"></i>{{ $project->location }}</div>
                    @endif
                    @if($project->description)
                    <div class="project-meta mt-1" style="font-size:0.78rem;color:#999">{{ Str::limit($project->description, 60) }}</div>
                    @endif
                </div>
            </button>
        </form>
        @endforeach
    </div>
    @endif

    <div class="user-bar">
        <span class="small text-muted">
            <i class="bi bi-person-circle me-1"></i>
            Logged in as <strong>{{ auth()->user()->name }}</strong>
            <span class="badge ms-1 {{ auth()->user()->isAdmin() ? 'bg-danger' : (auth()->user()->isViewer() ? 'bg-secondary' : 'bg-primary') }}" style="font-size:0.7rem">
                {{ ucfirst(auth()->user()->role) }}
            </span>
        </span>
        <form method="POST" action="{{ route('logout') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</div>
</body>
</html>
