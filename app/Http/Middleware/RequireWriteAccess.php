<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireWriteAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->canWrite()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Read-only access. You cannot perform this action.'], 403);
            }
            return redirect()->back()->with('error', '⛔ Your account is read-only. You cannot create, edit, or delete records.');
        }
        return $next($request);
    }
}
