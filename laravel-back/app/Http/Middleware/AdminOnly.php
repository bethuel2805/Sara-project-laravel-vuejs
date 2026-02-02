<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'message' => 'Accès refusé. Seuls les administrateurs peuvent gérer les utilisateurs.'
            ], 403);
        }

        return $next($request);
    }
}
