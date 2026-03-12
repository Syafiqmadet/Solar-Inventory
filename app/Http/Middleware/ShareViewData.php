<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\Project;

class ShareViewData
{
    public function handle(Request $request, Closure $next)
    {
        $canWrite       = false;
        $currentProject = null;

        if (Auth::check()) {
            $canWrite = Auth::user()->canWrite();

            $projectId = session('current_project_id');
            if ($projectId) {
                $currentProject = Project::find($projectId);
            }
        }

        View::share('canWrite', $canWrite);
        View::share('currentProject', $currentProject);

        return $next($request);
    }
}
