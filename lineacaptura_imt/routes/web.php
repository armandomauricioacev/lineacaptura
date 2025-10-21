<?php

use App\Http\Controllers\LineaCapturaController;
use Illuminate\Support\Facades\Route;

// Redirección inicial
Route::get('/', function () {
    return redirect('/inicio');
});

// Ruta pública de inicio con rate limiting básico
Route::get('/inicio', [LineaCapturaController::class, 'index'])->name('inicio')->middleware('throttle:60,1');

// Rutas del flujo de captura (protegidas por middleware y rate limiting)
Route::middleware(['web', 'ensure.step', 'throttle:40,1'])->group(function () {
    Route::get('/tramite', [LineaCapturaController::class, 'showTramite'])->name('tramite.show');
    Route::get('/persona', [LineaCapturaController::class, 'showPersonaForm'])->name('persona.show');
    Route::get('/pago', [LineaCapturaController::class, 'showPagoPage'])->name('pago.show');
});

// Rutas POST para el flujo con rate limiting
Route::post('/tramite', [LineaCapturaController::class, 'showTramite'])->name('tramite.store')->middleware('throttle:30,1');
Route::post('/persona', [LineaCapturaController::class, 'storeTramiteSelection'])->name('persona.store')->middleware('throttle:20,1');
Route::post('/pago', [LineaCapturaController::class, 'storePersonaData'])->name('pago.store')->middleware('throttle:15,1');
Route::post('/generar-linea', [LineaCapturaController::class, 'generarLineaCaptura'])->name('linea.generar')->middleware('throttle:20,1');

// Ruta GET para generar-linea que redirige a inicio (protección contra recarga/navegación)
Route::get('/generar-linea', function () {
    return redirect('/inicio');
})->name('linea.generar.redirect');

// ==========================================================
// RUTAS DE NAVEGACIÓN CON RATE LIMITING
// ==========================================================
Route::post('/regresar', [LineaCapturaController::class, 'regresar'])->name('regresar')->middleware('throttle:30,1');
Route::get('/regresar', [LineaCapturaController::class, 'regresar'])->name('regresar.get')->middleware('throttle:30,1');