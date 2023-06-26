<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = [
            // Add your allowed origins here
            // Example: Allow requests from a local development server
            'http://localhost:5173',

            // Example: Allow requests from your production domain
            // 'https://buzz.stack',  
        ];

        $origin = $request->header('Origin');

        if (in_array($origin, $allowedOrigins)) {
            return $next($request)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization');
        }

        return $next($request);
    }
}
