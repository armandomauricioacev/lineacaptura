<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que agrega encabezados de seguridad a las respuestas HTTP
 * e impone una política de seguridad de contenido (CSP) para reducir
 * riesgos de XSS, inyección de contenido y clickjacking.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Headers de seguridad básicos para endurecimiento de respuestas
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // Content Security Policy (CSP) con actualización forzada a HTTPS
        $csp = "upgrade-insecure-requests; " . // <-- ¡SOLUCIÓN! Forza HTTPS en todas las peticiones.
               "default-src 'self'; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://framework-gb.cdn.gob.mx https://eu2dpadevsta020.blob.core.windows.net; " .
               "font-src 'self' https://fonts.gstatic.com https://framework-gb.cdn.gob.mx; " .
               "img-src 'self' data: https://img.icons8.com https://framework-gb.cdn.gob.mx https://*.gob.mx https://eu2dpadevsta020.blob.core.windows.net https://eu2comdevsta102.blob.core.windows.net https://serviciosincronoqa.azurewebsites.net https://cudandevstalogosbancos.blob.core.windows.net; " .
               "script-src 'self' 'unsafe-inline' https://framework-gb.cdn.gob.mx https://cdn.jsdelivr.net https://ajax.googleapis.com; " . // <-- Corregido a https
               "connect-src 'self' https://*.gob.mx https://dpadepqa.cloudapp.net https://serviciosincronoqa.azurewebsites.net; " .
               "frame-ancestors 'none';";
        
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}