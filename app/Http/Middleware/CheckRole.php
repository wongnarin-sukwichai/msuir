<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $level = '1'): Response
    {
        if (! $request->user() || (int) $request->user()->role_level < (int) $level) {
            abort(403);
        }

        return $next($request);
    }
}
