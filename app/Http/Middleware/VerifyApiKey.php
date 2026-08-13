<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');

        if (! $apiKey || $apiKey !== env('LUMOA_API_KEY')) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized Access',
                'status_code' => 401,
                'data' => null,
            ], 401);
        }

        return $next($request);
    }
}
