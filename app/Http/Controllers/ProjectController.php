<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /** Show project selection screen (post-login) */
    public function select()
    {
        $projects = Auth::user()->accessibleProjects();

        // If only one project, auto-select it
        if ($projects->count() === 1) {
            session(['current_project_id' => $projects->first()->id]);
            return redirect()->route('dashboard');
        }

        return view('projects.select', compact('projects'));
    }

    /** Store chosen project in session */
    public function choose(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id']);

        $user      = Auth::user();
        $projectId = $request->project_id;

        $project = Project::findOrFail($projectId);

        // Block access to archived projects
        if (!$project->is_active) {
            return back()->with('error', 'This project has been archived and is no longer accessible.');
        }

        // Verify user assignment
        $hasAccess = $user->isAdmin()
            || $user->projects()->where('project_id', $projectId)->exists();

        if (!$hasAccess) {
            return back()->with('error', 'You do not have access to this project.');
        }

        session(['current_project_id' => $projectId]);
        return redirect()->route('dashboard');
    }

    /** Switch project (clears current and redirects to select) */
    public function switchProject()
    {
        session()->forget('current_project_id');
        return redirect()->route('projects.select');
    }

    // ── Admin CRUD ─────────────────────────────────────────────────────────

    public function index()
    {
        $projects = Project::withCount('users')->orderBy('name')->get();
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('projects.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'code'     => 'required|string|max:20|unique:projects',
            'color'    => 'nullable|string|max:20',
            'location' => 'nullable|string|max:100',
        ]);

        $project = Project::create($request->only('name','code','description','location','color','is_active'));
        $project->users()->sync($request->input('user_ids', []));

        return redirect()->route('projects.index')->with('success', "Project \"{$project->name}\" created!");
    }

    public function edit(Project $project)
    {
        $users           = User::orderBy('name')->get();
        $assignedUserIds = $project->users()->pluck('user_id')->toArray();
        return view('projects.edit', compact('project','users','assignedUserIds'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'code'  => 'required|string|max:20|unique:projects,code,'.$project->id,
            'color' => 'nullable|string|max:20',
        ]);

        $project->update($request->only('name','code','description','location','color','is_active'));
        $project->users()->sync($request->input('user_ids', []));

        return redirect()->route('projects.index')->with('success', "Project \"{$project->name}\" updated!");
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }

    public function toggle(Project $project)
    {
        $project->update(['is_active' => !$project->is_active]);

        if (!$project->is_active) {
            $msg = 'Project ' . $project->name . ' archived. Users will be redirected on their next action.';
        } else {
            $msg = 'Project ' . $project->name . ' restored. Users can now select it again.';
        }

        return redirect()->route('projects.index')->with('success', $msg);
    }
}