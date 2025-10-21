<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log de intento de acceso para monitoreo
        Log::info('🚦 Rate Limiting - Acceso monitoreado', [
            'ip' => $request->ip(),
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'user_agent' => $request->headers->get('user-agent'),
            'timestamp' => now()
        ]);

        $response = $next($request);

        // Si la respuesta es 429 (Too Many Requests), loggear el evento
        if ($response->getStatusCode() === 429) {
            Log::warning('🚨 Rate Limit Excedido', [
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'user_agent' => $request->headers->get('user-agent'),
                'timestamp' => now(),
                'headers' => [
                    'retry_after' => $response->headers->get('Retry-After'),
                    'x_ratelimit_limit' => $response->headers->get('X-RateLimit-Limit'),
                    'x_ratelimit_remaining' => $response->headers->get('X-RateLimit-Remaining')
                ]
            ]);
        }

        return $response;
    }
}