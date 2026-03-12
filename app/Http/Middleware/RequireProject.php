<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;

class RequireProject
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) return $next($request);

        $projectId = session('current_project_id');
        $user      = Auth::user();

        // Validate existing session project
        if ($projectId) {
            $project   = Project::find($projectId);
            $hasAccess = $user->isAdmin()
                || $user->projects()->where('project_id', $projectId)->exists();

            $projectInvalid = !$project || !$project->is_active || !$hasAccess;

            if ($projectInvalid) {
                $wasArchived = $project && !$project->is_active;
                session()->forget('current_project_id');
                $projectId = null;

                // Set a message so user knows why they were kicked out
                if ($wasArchived) {
                    session()->flash('error', 'The project "' . $project->name . '" has been archived by an administrator. Please select another project.');
                } elseif (!$hasAccess) {
                    session()->flash('error', 'You no longer have access to that project. Please select another.');
                }
            }
        }

        if (!$projectId) {
            // Admins: auto-select first active project
            if ($user->isAdmin()) {
                $first = Project::where('is_active', true)->orderBy('name')->first();
                if ($first) {
                    session(['current_project_id' => $first->id]);
                }
                // No active projects yet — let admin through to manage them
                return $next($request);
            }

            // Non-admins: allow selection/logout routes, block everything else
            if ($request->routeIs('projects.select', 'projects.choose', 'logout')) {
                return $next($request);
            }

            return redirect()->route('projects.select');
        }

        return $next($request);
    }
}
