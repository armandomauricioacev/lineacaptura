<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineasCapturadas extends Model
{
    use HasFactory;

    protected $table = 'lineas_capturadas';

    protected $fillable = [
        'tipo_persona',
        'curp',
        'rfc',
        'razon_social',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'dependencia_id',
        'tramite_id',
        'detalle_tramites_snapshot', // ✅ Campo del snapshot
        'solicitud',
        'importe_cuota',
        'importe_iva',
        'importe_total',
        'json_generado',
        'estado_pago',
        'fecha_solicitud',
        'fecha_vigencia',
        'json_recibido',
        'id_documento',
        'tipo_pago',
        'html_codificado',
        'resultado',
        'linea_captura',
        'importe_sat',
        'fecha_vigencia_sat',
        'errores_sat',
        'fecha_respuesta_sat',
        'procesado_exitosamente'
    ];

    /**
     * ✅ CRÍTICO: Cast del campo JSON para que Laravel maneje correctamente
     * la serialización y deserialización automática
     */
    protected $casts = [
        'detalle_tramites_snapshot' => 'array', // ✅ Convierte automáticamente JSON ↔ Array
        'fecha_solicitud' => 'date',
        'fecha_vigencia' => 'date',
        'fecha_vigencia_sat' => 'date',
        'fecha_respuesta_sat' => 'datetime',
        'procesado_exitosamente' => 'boolean',
        'importe_cuota' => 'decimal:2',
        'importe_iva' => 'decimal:2',
        'importe_total' => 'decimal:2',
        'importe_sat' => 'decimal:2',
    ];

    /**
     * Relación con la tabla dependencias
     */
    public function dependencia()
    {
        return $this->belongsTo(Dependencia::class, 'dependencia_id');
    }

    /**
     * Relación con la tabla tramites
     * Nota: tramite_id contiene un string con IDs separados por comas
     * Esta relación es más informativa, el snapshot contiene los datos reales
     */
    public function tramite()
    {
        return $this->belongsTo(Tramite::class, 'tramite_id');
    }

    /**
     * Obtener los trámites del snapshot (datos estáticos)
     * @return array|null
     */
    public function getTramitesSnapshot()
    {
        return $this->detalle_tramites_snapshot['tramites'] ?? null;
    }

    /**
     * Obtener el resumen del snapshot
     * @return array|null
     */
    public function getResumenSnapshot()
    {
        return $this->detalle_tramites_snapshot['resumen'] ?? null;
    }

    /**
     * Obtener la información de la dependencia del snapshot
     * @return array|null
     */
    public function getDependenciaSnapshot()
    {
        return $this->detalle_tramites_snapshot['dependencia'] ?? null;
    }

    /**
     * Verificar si tiene snapshot válido
     * @return bool
     */
    public function hasValidSnapshot()
    {
        return !empty($this->detalle_tramites_snapshot) 
            && isset($this->detalle_tramites_snapshot['tramites'])
            && is_array($this->detalle_tramites_snapshot['tramites'])
            && count($this->detalle_tramites_snapshot['tramites']) > 0;
    }
}