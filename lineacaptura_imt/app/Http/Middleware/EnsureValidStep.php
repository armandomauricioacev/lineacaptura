<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que valida el paso actual del flujo de trámites
 * en función de la sesión del usuario. Si intenta acceder a una
 * ruta que no corresponde a su estado, se le redirige a la correcta.
 */
class EnsureValidStep
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentRouteName = $request->route()->getName();
        $session = $request->session();

        // Determina el paso "correcto" en el que el usuario DEBERÍA estar.
        $correctStepRoute = 'inicio'; // Por defecto, todos deben estar en el inicio.

        if ($session->has('dependenciaId') && !$session->has('tramites_seleccionados')) {
            $correctStepRoute = 'tramite.show';
        }
        if ($session->has('tramites_seleccionados') && !$session->has('persona_data')) {
            $correctStepRoute = 'persona.show';
        }
        if ($session->has('persona_data')) {
            $correctStepRoute = 'pago.show';
        }

        // Excepción: Si el proceso ha finalizado, la única página válida es la de inicio.
        if ($session->has('linea_capturada_finalizada')) {
            $correctStepRoute = 'inicio';
        }

        // Si la ruta que el usuario intenta visitar NO es la que le corresponde...
        if ($currentRouteName !== $correctStepRoute) {
            // Log de intento de navegación no válida
            Log::warning('Intento de navegación no válida detectado', [
                'ruta_intentada' => $currentRouteName,
                'ruta_correcta' => $correctStepRoute,
                'ip' => $request->ip(),
                'user_agent' => $request->headers->get('user-agent'),
                'session_id' => $session->getId(),
                'tiene_dependencia' => $session->has('dependenciaId'),
                'tiene_tramites' => $session->has('tramites_seleccionados'),
                'tiene_persona' => $session->has('persona_data'),
                'proceso_finalizado' => $session->has('linea_capturada_finalizada'),
                'timestamp' => now()
            ]);

            // ...lo redirigimos a la fuerza a la página correcta.
            // Esto bloquea cualquier intento de navegar con la URL.
            return redirect()->route($correctStepRoute);
        }

        // Si está en la página correcta, le permitimos continuar.
        return $next($request);
    }
}