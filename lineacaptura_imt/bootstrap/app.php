<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'ensure.step' => \App\Http\Middleware\EnsureValidStep::class,
            'validate.flow' => \App\Http\Middleware\ValidateFlowStep::class,
            'rate.monitor' => \App\Http\Middleware\RateLimitingMiddleware::class,
        ]);

        // Aplicar encabezados de seguridad a todas las rutas web
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            // \App\Http\Middleware\RateLimitingMiddleware::class, // Temporalmente deshabilitado para debugging
        ]);

        // Redirigir usuarios no autenticados al login (usar URL directa, NO route())
        $middleware->redirectGuestsTo('/admin-login-form');
        
        // Después del login exitoso, ir al panel admin (usar URL directa, NO route())
        $middleware->redirectUsersTo('/admin-dashboard-panel');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Configuración de manejo de excepciones
        // Agrega aquí handlers personalizados si se requiere
    })->create();