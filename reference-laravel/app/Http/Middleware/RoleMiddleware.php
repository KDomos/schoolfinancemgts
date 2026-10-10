<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Use on routes as: RoleMiddleware::class.':Bursar,Director'  (comma-separated allowed roles) */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, $roles, true), 403, 'Your role cannot open this page.');
        return $next($request);
    }
}
