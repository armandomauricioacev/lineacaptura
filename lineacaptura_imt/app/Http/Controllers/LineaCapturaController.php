<?php

namespace App\Http\Controllers;

use App\Models\Dependencia;
use App\Models\Tramite;
use App\Models\LineasCapturadas;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controlador principal del flujo de la Línea de Captura.
 * Gestiona selección de dependencia y trámites, captura de datos
 * de persona y generación de la línea de captura, controlando sesión
 * y registrando eventos clave para auditoría y debugging.
 */
class LineaCapturaController extends Controller
{
    protected $cacheService;

    /**
     * Constructor: inyecta servicio de cache.
     */
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Muestra lista inicial de dependencias.
     */
    public function index(Request $request)
    {
        // Log de acceso a página inicial
        Log::info('Acceso a página inicial', [
            'ip' => $request->ip(),
            'user_agent' => $request->headers->get('user-agent'),
            'timestamp' => now()
        ]);

        // Limpia el flujo
        $request->session()->forget([
            'dependenciaId',
            'tramites_seleccionados',
            'persona_data',
            'linea_capturada_finalizada'
        ]);

        // Usar cache service en lugar de consulta directa
        $dependencias = $this->cacheService->getDependencias();

        // Vista: resources/views/forms/inicio.blade.php
        return view('forms.inicio', ['dependencias' => $dependencias]);
    }

    /**
     * Guarda dependencia seleccionada y redirige a trámites.
     */
    public function storeDependenciaSelection(Request $request)
    {
        $validated = $request->validate([
            'dependenciaId' => 'required|integer|exists:dependencias,id',
        ]);

        // Log de selección de dependencia
        Log::info('Dependencia seleccionada', [
            'dependencia_id' => $validated['dependenciaId'],
            'ip' => $request->ip(),
            'timestamp' => now()
        ]);

        $request->session()->put('dependenciaId', $validated['dependenciaId']);

        // REDIRECCIÓN para evitar error 419 en recargas
        return redirect()->route('tramite.show');
    }

    /**
     * Muestra selección de trámites.
     */
    public function showTramite(Request $request)
    {
        $dependenciaId = $request->session()->get('dependenciaId');
        if (!$dependenciaId) {
            // Log de intento de acceso sin dependencia
            Log::warning('Intento de acceso a trámites sin dependencia', [
                'ip' => $request->ip(),
                'user_agent' => $request->headers->get('user-agent'),
                'timestamp' => now()
            ]);
            
            return redirect()->route('inicio')->with('error', 'Por favor, selecciona una dependencia primero.');
        }

        // Usar cache service para obtener dependencia y trámites
        $dependencia = $this->cacheService->getDependencia($dependenciaId);
        $tramites = $this->cacheService->getTramitesByDependencia($dependenciaId);

        // Vista: resources/views/forms/tramite.blade.php
        return view('forms.tramite', [
            'dependencia' => $dependencia,
            'tramites'    => $tramites,
        ]);
    }

    /**
     * Guarda selección de trámites y cantidades.
     */
    public function storeTramiteSelection(Request $request)
    {
        // Log mejorado para debugging y seguridad
        Log::info('Seleccion de trámites iniciada', [
            'ip' => $request->ip(),
            'user_agent' => $request->headers->get('user-agent'),
            'dependencia_id' => $request->session()->get('dependenciaId'),
            'timestamp' => now()
        ]);

        $validated = $request->validate([
            'tramite_ids'        => 'required|array|min:1|max:10',
            'tramite_ids.*'      => 'integer|exists:tramites,id',
            'tramite_cantidades' => 'required|array|min:1|max:10',
            'tramite_cantidades.*' => 'integer|min:1|max:999',
        ]);

        // Combinar IDs con cantidades
        $tramitesConCantidades = [];
        for ($i = 0; $i < count($validated['tramite_ids']); $i++) {
            $tramitesConCantidades[] = [
                'id' => $validated['tramite_ids'][$i],
                'cantidad' => $validated['tramite_cantidades'][$i] ?? 1
            ];
        }

        $request->session()->put('tramites_seleccionados', $tramitesConCantidades);

        // Log de trámites guardados con más contexto
        Log::info('Trámites guardados en sesión', [
            'tramites_seleccionados' => $tramitesConCantidades,
            'cantidad_tramites' => count($tramitesConCantidades),
            'dependencia_id' => $request->session()->get('dependenciaId'),
            'ip' => $request->ip(),
            'timestamp' => now()
        ]);

        return redirect()->route('persona.show');
    }

    /**
     * Muestra formulario de datos de la persona.
     */
    public function showPersonaForm(Request $request)
    {
        if (
            !$request->session()->has('dependenciaId') ||
            !$request->session()->has('tramites_seleccionados')
        ) {
            return redirect()->route('tramite.show')
                ->with('error', 'Por favor, selecciona al menos un trámite primero.');
        }

        // Vista: resources/views/forms/persona.blade.php
        return view('forms.persona');
    }

    /**
     * Recarga formulario de persona desde sesión.
     */
    public function showPersonaReload(Request $request)
    {
        // Validar que tenga los datos necesarios en sesión
        if (!$request->session()->has('dependenciaId') || !$request->session()->has('tramites_seleccionados')) {
            return redirect()->route('inicio')->with('error', 'Por favor, inicia el proceso nuevamente.');
        }

        return $this->showPersonaForm($request);
    }

    /**
     * Valida y guarda datos de persona; redirige a pago.
     */
    public function storePersonaData(Request $request)
    {
        // Log de inicio de captura de datos personales
        Log::info('Captura de datos personales iniciada', [
            'tipo_persona' => $request->input('tipo_persona'),
            'ip' => $request->ip(),
            'dependencia_id' => $request->session()->get('dependenciaId'),
            'timestamp' => now()
        ]);

        $tipoPersona = $request->input('tipo_persona');

        $rules = ['tipo_persona' => 'required|in:fisica,moral'];

        if ($tipoPersona === 'fisica') {
            $rules += [
                'curp'             => 'required|string|size:18',
                'rfc'              => 'required|string|size:13',
                'nombres'          => 'required|string|max:60',
                'apellido_paterno' => 'required|string|max:60',
                'apellido_materno' => 'nullable|string|max:60',
            ];
        } else {
            $rules += [
                'rfc_moral'    => 'required|string|size:12',
                'razon_social' => 'required|string|max:120'
            ];
        }

        $validatedData = $request->validate($rules);

        if ($tipoPersona === 'moral') {
            $validatedData['rfc'] = $validatedData['rfc_moral'];
            unset($validatedData['rfc_moral']);
        }

        if ($tipoPersona === 'fisica' && empty($validatedData['apellido_materno'])) {
            $validatedData['apellido_materno'] = null;
        }

        $request->session()->put('persona_data', $validatedData);

        // Log de datos personales guardados (sin datos sensibles)
        Log::info('Datos personales validados y guardados', [
            'tipo_persona' => $tipoPersona,
            'tiene_rfc' => !empty($validatedData['rfc']),
            'tiene_curp' => !empty($validatedData['curp'] ?? null),
            'ip' => $request->ip(),
            'dependencia_id' => $request->session()->get('dependenciaId'),
            'timestamp' => now()
        ]);

        return redirect()->route('pago.show');
    }

    /**
     * Muestra resumen y opciones de pago.
     */
    public function showPagoPage(Request $request)
    {
        $dependenciaId = $request->session()->get('dependenciaId');
        $tramiteIds    = $request->session()->get('tramites_seleccionados');
        $personaData   = $request->session()->get('persona_data');

        if (!$dependenciaId || empty($tramiteIds) || !$personaData) {
            return redirect()->route('inicio')
                ->with('error', 'Tu sesión ha expirado, por favor inicia de nuevo.');
        }

        // Validar que tramiteIds sea un array válido
        if (!is_array($tramiteIds)) {
            return redirect()->route('inicio')
                ->with('error', 'Los trámites seleccionados no son válidos. Por favor, inicia el proceso nuevamente.');
        }

        // Extraer solo los IDs para obtener los trámites de la base de datos
        $soloIds = array_column($tramiteIds, 'id');
        
        // Usar cache service para obtener dependencia y trámites
        $dependencia = $this->cacheService->getDependencia($dependenciaId);
        $tramites    = $this->cacheService->getTramites($soloIds);
        
        // Agregar cantidad a cada trámite
        foreach ($tramites as $tramite) {
            $tramiteConCantidad = collect($tramiteIds)->firstWhere('id', $tramite->id);
            $tramite->cantidad = $tramiteConCantidad['cantidad'] ?? 1;
        }

        // Vista: resources/views/forms/pago.blade.php
        return view('forms.pago', compact('dependencia', 'tramites', 'personaData'));
    }

    /**
     * Recarga página de pago desde sesión.
     */
    public function showPagoReload(Request $request)
    {
        // Validar que tenga todos los datos necesarios en sesión
        if (!$request->session()->has('dependenciaId') || 
            !$request->session()->has('tramites_seleccionados') || 
            !$request->session()->has('persona_data')) {
            return redirect()->route('inicio')->with('error', 'Por favor, inicia el proceso nuevamente.');
        }

        // Validar que tramites_seleccionados sea un array válido
        $tramitesSeleccionados = $request->session()->get('tramites_seleccionados');
        if (!is_array($tramitesSeleccionados) || empty($tramitesSeleccionados)) {
            return redirect()->route('inicio')->with('error', 'Los trámites seleccionados no son válidos. Por favor, inicia el proceso nuevamente.');
        }

        return $this->showPagoPage($request);
    }

    /**
     * Muestra línea de captura y respuesta del SAT.
     */
    public function showLineaCapturada(Request $request)
    {
        // Si hay una línea capturada finalizada en sesión, mostrarla
        if ($request->session()->has('linea_capturada_finalizada')) {
            // Obtener la última línea capturada del usuario (simulamos por IP por ahora)
            $ultimaLinea = LineasCapturadas::with(['dependencia', 'tramite'])->latest()->first();
            
            if ($ultimaLinea) {
                $jsonArray = json_decode($ultimaLinea->json_generado, true);
                $respuestaSat = [
                    'exito' => $ultimaLinea->procesado_exitosamente,
                    'datos' => json_decode($ultimaLinea->json_recibido, true),
                    'html_decodificado' => $ultimaLinea->html_codificado ? base64_decode($ultimaLinea->html_codificado) : null
                ];
                
                return view('forms.lineacaptura', [
                    'lineaCapturada' => $ultimaLinea,
                    'jsonParaSat'    => json_encode($jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'respuestaSat'   => $respuestaSat,
                    'htmlContentForJs' => $respuestaSat['html_decodificado'] ?? null,
                    'htmlContentForJsEncoded' => json_encode($respuestaSat['html_decodificado'] ?? null),
                    'initializeHtmlContentScript' => 'initializeHtmlContent(' . json_encode($respuestaSat['html_decodificado'] ?? null) . ');'
                ]);
            }
        }
        
        // Si no hay línea capturada, redirigir al inicio
        return redirect()->route('inicio')->with('error', 'No hay línea de captura disponible. Por favor, inicia el proceso nuevamente.');
    }

    /**
     * Genera línea de captura y envía a SAT.
     */
    public function generarLineaCaptura(Request $request)
    {
        // Log de inicio de generación
        Log::info('Generación de línea de captura iniciada', [
            'ip' => $request->ip(),
            'user_agent' => $request->headers->get('user-agent'),
            'timestamp' => now()
        ]);

        $dependenciaId = $request->session()->get('dependenciaId');
        $tramiteIds    = $request->session()->get('tramites_seleccionados');
        $personaData   = $request->session()->get('persona_data');

        if (!$dependenciaId || empty($tramiteIds) || !$personaData) {
            // Log de intento de generación sin datos completos
            Log::warning('Intento de generación sin datos completos', [
                'tiene_dependencia' => !empty($dependenciaId),
                'tiene_tramites' => !empty($tramiteIds),
                'tiene_persona' => !empty($personaData),
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            return redirect()->route('inicio')
                ->with('error', 'Tu sesión ha expirado, por favor inicia de nuevo.');
        }

        // Validar que tramiteIds sea un array válido
        if (!is_array($tramiteIds)) {
            Log::warning('tramiteIds no es un array válido', [
                'tramiteIds' => $tramiteIds,
                'tipo' => gettype($tramiteIds),
                'ip' => $request->ip(),
                'timestamp' => now()
            ]);

            return redirect()->route('inicio')
                ->with('error', 'Los trámites seleccionados no son válidos. Por favor, inicia el proceso nuevamente.');
        }

        // Usar cache service para obtener dependencia y trámites
        $dependencia = $this->cacheService->getDependencia($dependenciaId);
        
        // Extraer solo los IDs para obtener los trámites de la base de datos
        $soloIds = array_column($tramiteIds, 'id');
        $tramites = $this->cacheService->getTramites($soloIds);
        
        // Agregar cantidad a cada trámite
        foreach ($tramites as $tramite) {
            $tramiteConCantidad = collect($tramiteIds)->firstWhere('id', $tramite->id);
            $tramite->cantidad = $tramiteConCantidad['cantidad'] ?? 1;
        }

        $totalCuotas = 0;
        $totalIvas   = 0;

        foreach ($tramites as $tramite) {
            $cantidad = $tramite->cantidad ?? 1;
            $cuotaTotal = $tramite->cuota * $cantidad;
            $totalCuotas += $cuotaTotal;
            if ($tramite->iva) {
                $totalIvas += round($cuotaTotal * 0.16, 2);
            }
        }

        // Total redondeado (entero) antes de persistir
        $importeTotalGeneralSinRedondear = $totalCuotas + $totalIvas;
        $importeTotalGeneralRedondeado   = round($importeTotalGeneralSinRedondear);

        // Crear string de trámites con cantidades para almacenar
        $tramiteString = collect($tramiteIds)->map(function($item) {
            return $item['id'] . ':' . $item['cantidad'];
        })->implode(',');

        // ==========================================================
        // CONSTRUCCIÓN DEL SNAPSHOT COMPLETO ANTES DE CREAR REGISTRO
        // ==========================================================
        $snapshotTramites = [
            'tramites' => [],
            'resumen' => [
                'total_tramites_seleccionados' => count($tramites),
                'suma_cuotas' => $totalCuotas,
                'suma_iva' => $totalIvas,
                'gran_total' => $importeTotalGeneralRedondeado
            ],
            'dependencia' => [
                'id' => $dependencia->id,
                'nombre' => $dependencia->nombre,
                'clave_dependencia' => $dependencia->clave_dependencia,
                'unidad_administrativa' => $dependencia->unidad_administrativa
            ],
            'fecha_generacion' => Carbon::now()->toDateTimeString()
        ];

        // Agregar cada trámite al snapshot CON TODOS LOS CAMPOS
        foreach ($tramites as $tramite) {
            $cantidad = $tramite->cantidad ?? 1;
            $cuotaUnitaria = $tramite->cuota;
            $cuotaTotal = $cuotaUnitaria * $cantidad;
            $montoIva = $tramite->iva ? round($cuotaTotal * 0.16, 2) : 0;
            $importeTotal = $cuotaTotal + $montoIva;

            $snapshotTramites['tramites'][] = [
                // Identificadores
                'tramite_id_original' => $tramite->id,
                'cantidad' => $cantidad,
                
                // Información básica
                'descripcion' => $tramite->descripcion,
                'clave_tramite' => $tramite->clave_tramite,
                'variante' => $tramite->variante,
                'clave_dependencia_siglas' => $tramite->clave_dependencia_siglas,
                'tramite_usoreservado' => $tramite->tramite_usoreservado,
                
                // Montos calculados
                'cuota_unitaria' => $cuotaUnitaria,
                'importe_cuota_total' => $cuotaTotal,
                'iva_unitario' => $tramite->iva ? round($cuotaUnitaria * 0.16, 2) : 0,
                'importe_iva_total' => $montoIva,
                'importe_total' => $importeTotal,
                
                // Información legal y vigencia
                'fundamento_legal' => $tramite->fundamento_legal,
                'vigencia_tramite_de' => $tramite->vigencia_tramite_de,
                'vigencia_tramite_al' => $tramite->vigencia_tramite_al,
                'vigencia_lineacaptura' => $tramite->vigencia_lineacaptura,
                'tipo_vigencia' => $tramite->tipo_vigencia,
                
                // Clasificación contable
                'clave_contable' => $tramite->clave_contable,
                'agrupador' => $tramite->agrupador,
                'tipo_agrupador' => $tramite->tipo_agrupador,
                'clave_periodicidad' => $tramite->clave_periodicidad,
                'clave_periodo' => $tramite->clave_periodo,
                
                // Características del trámite
                'obligatorio' => $tramite->obligatorio,
                'nombre_monto' => $tramite->nombre_monto,
                'variable' => $tramite->variable,
                'actualizacion' => $tramite->actualizacion,
                'recargos' => $tramite->recargos,
                'multa_correccionfiscal' => $tramite->multa_correccionfiscal,
                'compensacion' => $tramite->compensacion,
                'saldo_favor' => $tramite->saldo_favor,
                
                // Timestamps del registro original
                'created_at_original' => $tramite->created_at ? $tramite->created_at->toDateTimeString() : null,
                'updated_at_original' => $tramite->updated_at ? $tramite->updated_at->toDateTimeString() : null,
            ];
        }

        // ==========================================================
        // CREAR REGISTRO CON SNAPSHOT YA INCLUIDO
        // ==========================================================
        $lineaCapturada = LineasCapturadas::create([
            'tipo_persona'      => ($personaData['tipo_persona'] === 'fisica' ? 'F' : 'M'),
            'curp'              => $personaData['curp'] ?? null,
            'rfc'               => $personaData['rfc'] ?? null,
            'razon_social'      => $personaData['razon_social'] ?? null,
            'nombres'           => $personaData['nombres'] ?? null,
            'apellido_paterno'  => $personaData['apellido_paterno'] ?? null,
            'apellido_materno'  => $personaData['apellido_materno'] ?? null,
            'dependencia_id'    => $dependenciaId,
            'tramite_id'        => $tramiteString,
            'detalle_tramites_snapshot' => $snapshotTramites, // Snapshot completo incluido
            'importe_cuota'     => $totalCuotas,
            'importe_iva'       => $totalIvas,
            'importe_total'     => $importeTotalGeneralRedondeado,
            'fecha_vigencia'    => Carbon::now()->addMonth()->toDateString(),
        ]);

        $jsonArray = $this->buildFullJsonForMultiple($lineaCapturada, $dependencia, $tramites);

        $lineaCapturada->solicitud     = $jsonArray['DatosGenerales']['Solicitud'];
        $lineaCapturada->json_generado = json_encode($jsonArray);
        $lineaCapturada->save();

        // ==========================================================
        //  INTEGRACIÓN CON API DEL SAT
        // ==========================================================
        $respuestaSat = $this->enviarJsonASat($jsonArray);
        
        if ($respuestaSat['exito']) {
            // Procesar respuesta exitosa del SAT
            $datosProcesados = $this->procesarRespuestaSat($respuestaSat['datos'], json_encode($respuestaSat['datos']));
            
            // Actualizar el registro con la respuesta del SAT
            $lineaCapturada->update([
                'json_recibido' => json_encode($respuestaSat['datos']),
                'id_documento' => $datosProcesados['id_documento'] ?? null,
                'tipo_pago' => $datosProcesados['tipo_pago'] ?? null,
                'html_codificado' => $datosProcesados['html_codificado'] ?? null,
                'resultado' => $datosProcesados['resultado'] ?? null,
                'linea_captura' => $datosProcesados['linea_captura'] ?? null,
                'importe_sat' => $datosProcesados['importe_sat'] ?? null,
                'fecha_vigencia_sat' => $datosProcesados['fecha_vigencia_sat'] ?? null,
                'errores_sat' => null,
                'fecha_respuesta_sat' => now(),
                'procesado_exitosamente' => true
            ]);
            
            // Agregar HTML decodificado a la respuesta para la vista
            $respuestaSat['html_decodificado'] = $datosProcesados['html_decodificado'] ?? null;
        } else {
            // Guardar errores del SAT
            $lineaCapturada->update([
                'errores_sat' => json_encode(['error' => $respuestaSat['error'] ?? 'Error desconocido']),
                'fecha_respuesta_sat' => now(),
                'procesado_exitosamente' => false
            ]);
        }

        // Reset del flujo y bandera final
        $request->session()->flush();
        $request->session()->put('linea_capturada_finalizada', true);

        // Log final de línea de captura generada exitosamente
        Log::info('Línea de captura generada exitosamente', [
            'linea_id' => $lineaCapturada->id,
            'solicitud' => $lineaCapturada->solicitud,
            'dependencia_id' => $dependenciaId,
            'cantidad_tramites' => count($tramiteIds),
            'importe_total' => $importeTotalGeneralRedondeado,
            'tipo_persona' => $personaData['tipo_persona'],
            'procesado_exitosamente' => $respuestaSat['exito'] ?? false,
            'snapshot_creado' => !empty($snapshotTramites),
            'ip' => $request->ip(),
            'timestamp' => now()
        ]);

        // Vista: resources/views/forms/lineacaptura.blade.php
        return view('forms.lineacaptura', [
            'lineaCapturada' => $lineaCapturada,
            'jsonParaSat'    => json_encode($jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'respuestaSat'   => $respuestaSat,
            'htmlContentForJs' => $respuestaSat['html_decodificado'] ?? null,
            'htmlContentForJsEncoded' => json_encode($respuestaSat['html_decodificado'] ?? null),
            'initializeHtmlContentScript' => 'initializeHtmlContent(' . json_encode($respuestaSat['html_decodificado'] ?? null) . ');'
        ]);
    }

    // ----------------- PRIVADAS -----------------

    private function buildFullJsonForMultiple(LineasCapturadas $linea, Dependencia $dep, $tramites): array
    {
        $idSolicitud = $dep->clave_dependencia . $dep->unidad_administrativa . date('y') . str_pad($linea->id, 10, '0', STR_PAD_LEFT);

        return [
            'DatosGenerales' => $this->buildDatosGenerales($idSolicitud, $linea, $dep),
            'Tramites'       => $this->buildTramitesForMultiple($tramites, $linea->importe_total),
        ];
    }

    private function buildDatosGenerales(string $idSolicitud, LineasCapturadas $linea, Dependencia $dep): array
    {
        $datos = [
            'Solicitud'          => $idSolicitud,
            'CveDependencia'     => $dep->clave_dependencia,
            'UnidadAdministrativa'=> $dep->unidad_administrativa,
            'TipoPersona'        => $linea->tipo_persona,
            'RFC'                => $linea->rfc,
        ];

        if ($linea->tipo_persona === 'F') {
            $datos['CURP']           = $linea->curp;
            $datos['Nombre']         = $linea->nombres;
            $datos['ApellidoPaterno']= $linea->apellido_paterno;
            $datos['ApellidoMaterno']= $linea->apellido_materno;
        } else {
            $datos['RazonSocial'] = $linea->razon_social;
        }

        $datos['DatosLineaCaptura'] = [
            'FechaSolicitud' => Carbon::parse($linea->created_at)->format('d/m/Y H:i'),
            'Importe'        => $linea->importe_total,
            'FechaVigencia'  => Carbon::parse($linea->fecha_vigencia)->format('d/m/Y'),
        ];

        return $datos;
    }

    private function buildTramitesForMultiple($tramites, $totalRedondeado): array
    {
        $tramitesArray = [];
        $numeroSecuenciaGlobal = 1;

        $totalSinRedondear = 0;
        foreach ($tramites as $t) {
            $cantidad = $t->cantidad ?? 1;
            $cuotaTotal = $t->cuota * $cantidad;
            $montoIva = $t->iva ? round($cuotaTotal * 0.16, 2) : 0;
            $totalSinRedondear += $cuotaTotal + $montoIva;
        }

        $diferenciaRedondeo = $totalRedondeado - $totalSinRedondear;

        foreach ($tramites as $index => $tramite) {
            $cantidad = $tramite->cantidad ?? 1;
            $cuotaTotal = $tramite->cuota * $cantidad;
            $montoIva   = $tramite->iva ? round($cuotaTotal * 0.16, 2) : 0;
            $totalTram  = $cuotaTotal + $montoIva;

            if ($index === count($tramites) - 1) {
                $totalTram += $diferenciaRedondeo;
            }

            $conceptos = [];
            $conceptos[] = $this->buildConcepto($numeroSecuenciaGlobal++, 'P', $tramite, $cuotaTotal);

            if ($tramite->iva) {
                $montoIvaAjustado = $montoIva;
                if ($index === count($tramites) - 1) {
                    $montoIvaAjustado += $diferenciaRedondeo;
                }
                $conceptos[] = $this->buildConcepto($numeroSecuenciaGlobal++, 'S', $tramite, $montoIvaAjustado, '130009');
            }

            $tramitesArray[] = [
                'NumeroTramite'   => $index + 1,
                'Homoclave'       => $tramite->clave_tramite,
                'Variante'        => $tramite->variante,
                'NumeroConceptos' => count($conceptos),
                'TotalTramite'    => round($totalTram, 2),
                'Conceptos'       => ['Concepto' => $conceptos],
            ];
        }

        return ['Tramite' => $tramitesArray];
    }

    private function buildConcepto(int $secuencia, string $tipoAgrupador, Tramite $tramite, float $monto, ?string $claveConcepto = null): array
    {
        $monto = round($monto, 2);

        $transacciones = [
            ['ClaveTransaccion' => '4011', 'ValorTransaccion' => $monto],
            ['ClaveTransaccion' => '4243', 'ValorTransaccion' => $monto],
            ['ClaveTransaccion' => '4423', 'ValorTransaccion' => $monto],
        ];

        return [
            'NumeroSecuencia'   => $secuencia,
            'ClaveConcepto'     => $claveConcepto ?? (string) $tramite->clave_contable,
            'Agrupador'         => [
                'IdAgrupador'  => (int) $tramite->agrupador,
                'TipoAgrupador'=> $tipoAgrupador
            ],
            'DatosIcep'         => [
                'ClavePeriodicidad' => $tramite->clave_periodicidad,
                'ClavePeriodo'      => $tramite->clave_periodo,
                'FechaCausacion'    => Carbon::now()->format('d/m/Y')
            ],
            'TotalContribuciones' => $monto,
            'TotalConcepto'       => $monto,
            'DP'                  => ['TransaccionP' => $transacciones],
        ];
    }

    /**
     * Regresa al paso anterior del flujo.
     */
    public function regresar(Request $request)
    {
        $paso_actual = $request->input('paso_actual');

        if ($paso_actual === 'tramite') {
            $request->session()->forget('dependenciaId');
            return redirect()->route('inicio');
        }

        if ($paso_actual === 'persona') {
            $request->session()->forget('tramites_seleccionados');
            return redirect()->route('tramite.show');
        }

        if ($paso_actual === 'pago') {
            $request->session()->forget('persona_data');
            return redirect()->route('persona.show');
        }

        return redirect()->route('inicio');
    }

    /**
     * Valida JSON y decodifica HTML base64.
     */
    public function validarYDecodificarJson(string $rutaArchivo): array
    {
        try {
            // Verificar existencia del archivo
            if (!file_exists($rutaArchivo)) {
                return [
                    'exito' => false,
                    'error' => 'El archivo no existe en la ruta especificada',
                    'ruta' => $rutaArchivo
                ];
            }

            // Leer contenido del archivo
            $contenido = file_get_contents($rutaArchivo);
            $tamanoArchivo = strlen($contenido);

            // Información básica del archivo
            $info = [
                'tamano_bytes' => $tamanoArchivo,
                'primeros_100_chars' => substr($contenido, 0, 100),
                'ultimos_100_chars' => substr($contenido, -100)
            ];

            // Limpiar contenido de posibles caracteres problemáticos
            $contenidoLimpio = trim($contenido);

            // Verificar formato JSON básico
            if (!str_ends_with($contenidoLimpio, '}')) {
                return [
                    'exito' => false,
                    'error' => 'El archivo JSON está incompleto - no termina con "}"',
                    'info' => $info,
                    'diagnostico' => [
                        'termina_con_llave' => false,
                        'ultimo_caracter' => substr($contenidoLimpio, -1)
                    ]
                ];
            }

            // Buscar caracteres de control problemáticos
            $caracteresControl = [];
            for ($i = 0; $i < strlen($contenido); $i++) {
                $ascii = ord($contenido[$i]);
                if ($ascii < 32 && $ascii !== 9 && $ascii !== 10 && $ascii !== 13) {
                    $caracteresControl[] = [
                        'posicion' => $i,
                        'ascii' => $ascii,
                        'hex' => dechex($ascii)
                    ];
                }
            }

            // Verificar balance de llaves
            $llaves_abiertas = substr_count($contenido, '{');
            $llaves_cerradas = substr_count($contenido, '}');

            // Intentar decodificar JSON
            $datosJson = json_decode($contenido, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'exito' => false,
                    'error' => 'Error al decodificar JSON: ' . json_last_error_msg(),
                    'info' => $info,
                    'diagnostico' => [
                        'codigo_error_json' => json_last_error(),
                        'caracteres_control' => $caracteresControl,
                        'balance_llaves' => [
                            'abiertas' => $llaves_abiertas,
                            'cerradas' => $llaves_cerradas,
                            'balanceado' => $llaves_abiertas === $llaves_cerradas
                        ]
                    ]
                ];
            }

            // Validar estructura esperada
            $estructura = [
                'tiene_datos_generales' => isset($datosJson['DatosGenerales']),
                'tiene_acuse' => isset($datosJson['Acuse']),
                'tiene_html' => isset($datosJson['Acuse']['HTML'])
            ];

            if (!$estructura['tiene_html']) {
                return [
                    'exito' => false,
                    'error' => 'No se encontró el campo HTML en la estructura JSON',
                    'info' => $info,
                    'estructura' => $estructura,
                    'claves_disponibles' => array_keys($datosJson)
                ];
            }

            // Decodificar HTML base64
            $htmlBase64 = $datosJson['Acuse']['HTML'];
            $htmlDecodificado = html_entity_decode(base64_decode($htmlBase64), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Verificar que la decodificación fue exitosa
            if (empty($htmlDecodificado)) {
                return [
                    'exito' => false,
                    'error' => 'Error al decodificar el HTML base64',
                    'info' => $info,
                    'html_info' => [
                        'longitud_base64' => strlen($htmlBase64),
                        'primeros_50_chars' => substr($htmlBase64, 0, 50)
                    ]
                ];
            }

            // Éxito - retornar todos los datos
            return [
                'exito' => true,
                'mensaje' => 'JSON validado y HTML decodificado exitosamente',
                'info' => $info,
                'estructura' => $estructura,
                'datos_json' => $datosJson,
                'html_base64' => $htmlBase64,
                'html_decodificado' => $htmlDecodificado,
                'estadisticas' => [
                    'caracteres_control_encontrados' => count($caracteresControl),
                    'longitud_html_base64' => strlen($htmlBase64),
                    'longitud_html_decodificado' => strlen($htmlDecodificado),
                    'balance_llaves_correcto' => $llaves_abiertas === $llaves_cerradas
                ]
            ];

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => 'Excepción durante el procesamiento: ' . $e->getMessage(),
                'archivo' => $rutaArchivo
            ];
        }
    }

    /**
     * Corrige JSON truncado removiendo caracteres finales inválidos.
     */
    public function corregirJsonTruncado(string $rutaArchivo): array
    {
        try {
            if (!file_exists($rutaArchivo)) {
                return [
                    'exito' => false,
                    'error' => 'El archivo no existe'
                ];
            }

            $contenido = file_get_contents($rutaArchivo);
            $contenidoOriginal = $contenido;
            
            // Limpiar caracteres problemáticos al final
            $contenido = rtrim($contenido, ' "');
            
            // Verificar si necesita corrección
            if ($contenido === $contenidoOriginal) {
                return [
                    'exito' => true,
                    'mensaje' => 'El archivo no necesitaba corrección',
                    'cambios_realizados' => false
                ];
            }

            // Guardar archivo corregido
            file_put_contents($rutaArchivo, $contenido);

            return [
                'exito' => true,
                'mensaje' => 'Archivo JSON corregido exitosamente',
                'cambios_realizados' => true,
                'caracteres_removidos' => strlen($contenidoOriginal) - strlen($contenido)
            ];

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error al corregir archivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envía JSON al SAT y retorna respuesta.
     */
    private function enviarJsonASat($jsonData)
    {
        // Obtener configuración de la API del SAT desde el archivo .env
        $satApiUrl = env('SAT_API_URL', 'https://api.sat.gob.mx/validacion/linea-captura');
        $satApiToken = env('SAT_API_TOKEN', null);
        $satApiKey = env('SAT_API_KEY', null);
        $satSubscriptionKey = env('SAT_SUBSCRIPTION_KEY', null);
        $timeout = env('SAT_API_TIMEOUT', 30);
        $connectTimeout = env('SAT_API_CONNECT_TIMEOUT', 10);
        $curlVerbose = filter_var(env('SAT_CURL_VERBOSE', false), FILTER_VALIDATE_BOOLEAN);
        $http2 = filter_var(env('SAT_HTTP2', false), FILTER_VALIDATE_BOOLEAN);
        $apimTrace = filter_var(env('SAT_APIM_TRACE', false), FILTER_VALIDATE_BOOLEAN);
        $acceptLanguage = env('SAT_ACCEPT_LANGUAGE', null);
        $sendCorrelationId = filter_var(env('SAT_SEND_CORRELATION_ID', true), FILTER_VALIDATE_BOOLEAN);
        $explicitCorrelationId = env('SAT_CORRELATION_ID', null);
        // JWT/Authorization configuration
        $jwtEnable = filter_var(env('SAT_JWT_ENABLE', false), FILTER_VALIDATE_BOOLEAN);
        $jwtSecret = env('SAT_JWT_SECRET', null);
        $jwtAud = env('SAT_JWT_AUD', 'www.sat.gob.mx');
        $jwtIss = env('SAT_JWT_ISS', null);
        $jwtSub = env('SAT_JWT_SUB', null);
        $jwtExpSeconds = intval(env('SAT_JWT_EXP_SECONDS', 300));
        $authScheme = strtoupper(env('SAT_AUTH_SCHEME', 'BEARER'));

        // Uso exclusivo de variables de entorno (.env)
        
        // Configuración mTLS (certificado cliente y CA) desde .env
        $clientCertPath = env('SAT_CLIENT_CERT_PATH', null);
        $clientCertType = strtoupper(env('SAT_CLIENT_CERT_TYPE', 'PEM'));
        $clientCertPassword = env('SAT_CLIENT_CERT_PASSWORD', null);
        $clientKeyPath = env('SAT_CLIENT_KEY_PATH', null);
        $clientKeyPassword = env('SAT_CLIENT_KEY_PASSWORD', null);
        $caCertPath = env('SAT_CA_CERT_PATH', null);
        $tlsVerify = filter_var(env('SAT_TLS_VERIFY', true), FILTER_VALIDATE_BOOLEAN);
        
        // Preparar headers para la petición
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: LineaCaptura-IMT/1.0'
        ];
        
        // Accept-Language si se configura (por defecto es-MX)
        if ($acceptLanguage) {
            $headers[] = 'Accept-Language: ' . $acceptLanguage;
        }
        
        // Opcional: Ocp-Apim-Trace para debug en APIM
        if ($apimTrace) {
            $headers[] = 'Ocp-Apim-Trace: true';
        }
        
        // Correlation ID para trazabilidad en Azure/APIM
        $correlationId = $explicitCorrelationId ?: Str::uuid()->toString();
        if ($sendCorrelationId) {
            $headers[] = 'x-correlation-id: ' . $correlationId;
        }
        
        // Agregar token de autenticación (JWT HS256 opcional)
        $authorizationToken = null;
        if ($jwtEnable && $jwtSecret) {
            $now = time();
            $payload = [
                'aud' => $jwtAud,
                'iat' => $now,
                'exp' => $now + max(60, $jwtExpSeconds),
            ];
            if (!empty($jwtIss)) { $payload['iss'] = $jwtIss; }
            if (!empty($jwtSub)) { $payload['sub'] = $jwtSub; }
            // JTI con correlationId para trazabilidad
            $payload['jti'] = ($explicitCorrelationId ?: Str::uuid()->toString());

            $authorizationToken = $this->generateJwtHs256($payload, $jwtSecret);
        } elseif ($satApiToken) {
            $authorizationToken = $satApiToken;
        }

        if ($authorizationToken) {
            if ($authScheme === 'PLAIN') {
                $headers[] = 'Authorization: ' . $authorizationToken;
            } else {
                $headers[] = 'Authorization: Bearer ' . $authorizationToken;
            }
        }
        
        // Agregar API Key si está configurada
        if ($satApiKey) {
            $headers[] = 'X-API-Key: ' . $satApiKey;
        }
        
        // Agregar clave de suscripción de Azure API Management si está configurada
        if ($satSubscriptionKey) {
            $headers[] = 'Ocp-Apim-Subscription-Key: ' . $satSubscriptionKey;
            // Fallback: algunas APIs de Azure requieren query param 'subscription-key'
            // Añadimos el parámetro también en la URL para máxima compatibilidad
            if (stripos($satApiUrl, 'subscription-key=') === false) {
                $delimiter = (strpos($satApiUrl, '?') !== false) ? '&' : '?';
                $satApiUrl .= $delimiter . 'subscription-key=' . urlencode($satSubscriptionKey);
            }
        }
        
        // Inicializar cURL
        $curl = curl_init();
        
        // Construir opciones cURL con soporte para mTLS
        $curlOpts = [
            CURLOPT_URL => $satApiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => $http2 ? CURL_HTTP_VERSION_2TLS : CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($jsonData),
            CURLOPT_HTTPHEADER => $headers,
        ];
        
        // Tiempo de conexión y verbose opcional
        $curlOpts[CURLOPT_CONNECTTIMEOUT] = $connectTimeout;
        if ($curlVerbose) {
            $curlOpts[CURLOPT_VERBOSE] = true;
        }

        // Verificación TLS (recomendada en producción)
        $curlOpts[CURLOPT_SSL_VERIFYPEER] = $tlsVerify;
        $curlOpts[CURLOPT_SSL_VERIFYHOST] = $tlsVerify ? 2 : 0;

        // Certificado de cliente (mTLS)
        if ($clientCertPath) {
            $curlOpts[CURLOPT_SSLCERT] = $clientCertPath;
            if ($clientCertType) {
                $curlOpts[CURLOPT_SSLCERTTYPE] = $clientCertType;
            }
            if ($clientCertPassword) {
                $curlOpts[CURLOPT_SSLCERTPASSWD] = $clientCertPassword;
            }
        }

        // Llave privada separada (cuando el cert es PEM y la llave va aparte)
        if ($clientKeyPath) {
            $curlOpts[CURLOPT_SSLKEY] = $clientKeyPath;
            if ($clientKeyPassword) {
                $curlOpts[CURLOPT_SSLKEYPASSWD] = $clientKeyPassword;
            }
        }

        // Certificado de CA/chain proporcionado por SAT
        if ($caCertPath) {
            $curlOpts[CURLOPT_CAINFO] = $caCertPath;
        }

        curl_setopt_array($curl, $curlOpts);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        // Verificar errores de conexión
        if ($error) {
            return [
                'exito' => false,
                'error' => 'Error de conexión con la API del SAT: ' . $error,
                'codigo_http' => 0,
                'correlation_id' => $correlationId
            ];
        }
        
        // Verificar código de respuesta HTTP
        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'exito' => false,
                'error' => 'La API del SAT respondió con código HTTP: ' . $httpCode,
                'codigo_http' => $httpCode,
                'respuesta_cruda' => $response,
                'content_type' => $contentType,
                'correlation_id' => $correlationId
            ];
        }
        
        // Decodificar respuesta JSON
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'exito' => false,
                'error' => 'Error al decodificar la respuesta JSON del SAT: ' . json_last_error_msg(),
                'codigo_http' => $httpCode,
                'respuesta_cruda' => $response,
                'correlation_id' => $correlationId
            ];
        }
        
        return [
            'exito' => true,
            'datos' => $responseData,
            'codigo_http' => $httpCode,
            'content_type' => $contentType,
            'correlation_id' => $correlationId
        ];
    }

    /**
     * Procesa respuesta del SAT y extrae datos clave.
     */
    private function procesarRespuestaSat(array $datosRespuesta, string $respuestaCompleta): array
    {
        try {
            $resultadoBase = [
                'exito' => true,
                'mensaje' => 'Respuesta del SAT procesada exitosamente',
                'json_completo' => $datosRespuesta,
            ];

            // Caso 1: Estructura tipo Acuse { HTML, LineaCaptura, ... }
            if (isset($datosRespuesta['Acuse']) && is_array($datosRespuesta['Acuse'])) {
                $acuse = $datosRespuesta['Acuse'];
                $htmlCodificado = $acuse['HTML'] ?? null;
                $htmlDecodificado = $htmlCodificado ? html_entity_decode(base64_decode($htmlCodificado), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;

                $resultado = array_merge($resultadoBase, [
                    'id_documento' => $acuse['IdDocumento'] ?? null,
                    'tipo_pago' => $acuse['TipoPago'] ?? null,
                    'html_codificado' => $htmlCodificado,
                    'html_decodificado' => $htmlDecodificado,
                    'resultado' => $acuse['Resultado'] ?? null,
                    'linea_captura' => $acuse['LineaCaptura'] ?? null,
                    'importe_sat' => $acuse['Importe'] ?? null,
                    'fecha_vigencia_sat' => $acuse['FechaVigencia'] ?? null,
                    'errores' => $acuse['Errores'] ?? null,
                    'datos_adicionales' => [
                        'solicitud_id' => $acuse['Solicitud'] ?? null,
                        'fecha_proceso' => $acuse['FechaProceso'] ?? null,
                        'codigo_respuesta' => $acuse['CodigoRespuesta'] ?? null
                    ]
                ]);

                if (!$htmlCodificado) {
                    $resultado['exito'] = false;
                    $resultado['mensaje'] = 'La respuesta del SAT no contiene el HTML codificado';
                    $resultado['errores'] = ['html_faltante' => 'No se encontró HTML en la respuesta'];
                }

                return $resultado;
            }

            // Caso 2: Estructura directa con campos "formatoHTML" y "linea Captura"
            $htmlCodificado = $this->buscarCampoFlexible($datosRespuesta, ['formatoHTML', 'FormatoHTML', 'htmlCodificado', 'HTML']);
            $lineaCaptura = $this->buscarCampoFlexible($datosRespuesta, ['linea Captura', 'LineaCaptura', 'lineaCaptura', 'linea_captura']);
            $importe = $this->buscarCampoFlexible($datosRespuesta, ['importe', 'Importe', 'monto', 'Monto']);
            $vigencia = $this->buscarCampoFlexible($datosRespuesta, ['fechaVigencia', 'FechaVigencia', 'vigencia', 'Vigencia']);

            if ($htmlCodificado) {
                $htmlDecodificado = html_entity_decode(base64_decode($htmlCodificado), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } else {
                $htmlDecodificado = null;
            }

            $resultado = array_merge($resultadoBase, [
                'html_codificado' => $htmlCodificado,
                'html_decodificado' => $htmlDecodificado,
                'linea_captura' => $lineaCaptura,
                'importe_sat' => $importe,
                'fecha_vigencia_sat' => $vigencia,
            ]);

            if (!$htmlCodificado) {
                $resultado['exito'] = false;
                $resultado['mensaje'] = 'La respuesta del SAT no contiene el HTML codificado';
                $resultado['errores'] = ['html_faltante' => 'No se encontró HTML/formatoHTML en la respuesta'];
            }

            return $resultado;

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al procesar la respuesta del SAT',
                'errores' => [
                    'excepcion' => $e->getMessage(),
                    'datos_recibidos' => $datosRespuesta
                ]
            ];
        }
    }

    /**
     * Genera un JWT HS256 con claims dados.
     */
    private function generateJwtHs256(array $claims, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [];
        $segments[] = $this->base64UrlEncode(json_encode($header));
        $segments[] = $this->base64UrlEncode(json_encode($claims));
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = $this->base64UrlEncode($signature);
        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Busca un campo en un arreglo de manera flexible (ignora mayúsculas, espacios y guiones bajos).
     */
    private function buscarCampoFlexible(array $arr, array $posiblesClaves)
    {
        $normalizar = function ($s) {
            return strtolower(str_replace([' ', '_'], '', $s));
        };
        $mapa = [];
        foreach ($arr as $k => $v) {
            $mapa[$normalizar($k)] = $v;
        }
        foreach ($posiblesClaves as $clave) {
            $n = $normalizar($clave);
            if (array_key_exists($n, $mapa)) {
                return $mapa[$n];
            }
        }
        return null;
    }
}