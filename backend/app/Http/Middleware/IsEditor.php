<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsEditor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || (!$request->user()->isEditor() && !$request->user()->isAdmin())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Editor or Admin access required.'
            ], 403);
        }

        return $next($request);
    }
}
