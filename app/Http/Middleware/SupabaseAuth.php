<?php

namespace App\Http\Middleware;

use App\Services\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupabaseAuth
{
    public function __construct(private SupabaseService $supabaseService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token required'], 401);
        }

        try {
            $user = $this->supabaseService->verifyToken($token);

            if (!$user) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            // Set the authenticated user in the request
            $request->setUserResolver(fn () => (object) $user);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed'], 401);
        }
    }
}