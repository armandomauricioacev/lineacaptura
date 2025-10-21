<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateFlowStep
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $step): Response
    {
        // Permitir todas las rutas de administración
        if ($request->is('admin/*') || $request->is('login') || $request->is('logout')) {
            return $next($request);
        }

        // Permitir la página de inicio sin validaciones
        if ($step === 'inicio') {
            return $next($request);
        }

        // Temporalmente deshabilitado para debugging móvil - solo log para análisis
        if ($this->isNavigationAttempt($request)) {
            Log::info('Navegación detectada', [
                'step' => $step,
                'method' => $request->method(),
                'url' => $request->url(),
                'referer' => $request->headers->get('referer'),
                'user_agent' => $request->headers->get('user-agent'),
                'is_mobile' => $this->isMobileDevice($request)
            ]);
            // Comentado temporalmente para debugging
            // return redirect()->route('inicio')->with('info', 'Por favor, utiliza los botones del formulario para navegar.');
        }

        // Validar flujo según el paso
        if (!$this->isValidNavigation($request, $step)) {
            return redirect()->route('inicio')->with('error', 'Debes completar los pasos anteriores primero.');
        }

        return $next($request);
    }
    
    /**
     * Detectar si es un intento de navegación del navegador o recarga
     */
    private function isNavigationAttempt(Request $request): bool
    {
        // Temporalmente deshabilitado para debugging móvil
        // Solo bloquear navegación externa de dominios completamente diferentes
        if ($request->isMethod('GET')) {
            $referer = $request->headers->get('referer');
            
            // Solo bloquear si el referer es de un dominio completamente diferente (no localhost/127.0.0.1)
            if ($referer && 
                !str_contains($referer, $request->getHost()) && 
                !str_contains($referer, 'localhost') && 
                !str_contains($referer, '127.0.0.1') &&
                !str_contains($referer, 'lineacaptura')) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Detectar si es un dispositivo móvil
     */
    private function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->headers->get('user-agent', '');
        return preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);
    }

    /**
     * Validar navegación según el flujo
     */
    private function isValidNavigation(Request $request, string $step): bool
    {
        $session = $request->session();
        
        // Definir los pasos del flujo y sus requisitos
        $flowSteps = [
            'inicio' => [],
            'tramite' => ['dependenciaId'],
            'persona' => ['dependenciaId', 'tramites_seleccionados'],
            'pago' => ['dependenciaId', 'tramites_seleccionados', 'persona_data'],
            'generar' => ['dependenciaId', 'tramites_seleccionados', 'persona_data']
        ];
        
        // Verificar si el paso es válido
        if (!array_key_exists($step, $flowSteps)) {
            return false;
        }
        
        // Verificar que se tengan todos los datos requeridos para este paso
        $requiredData = $flowSteps[$step];
        foreach ($requiredData as $key) {
            if (!$session->has($key)) {
                return false;
            }
        }
        
        return true;
    }
}