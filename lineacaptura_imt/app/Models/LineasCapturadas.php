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
        'detalle_tramites_snapshot', 
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
     * Casts para manejo automático de serialización/deserialización.
     */
    protected $casts = [
        'detalle_tramites_snapshot' => 'array', // Convierte automáticamente JSON ↔ Array
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
     * Relación con Dependencia.
     */
    public function dependencia()
    {
        return $this->belongsTo(Dependencia::class, 'dependencia_id');
    }

    /**
     * Relación informativa con Tramite (IDs en string).
     */
    public function tramite()
    {
        return $this->belongsTo(Tramite::class, 'tramite_id');
    }

    /**
     * Trámites del snapshot.
     */
    public function getTramitesSnapshot()
    {
        return $this->detalle_tramites_snapshot['tramites'] ?? null;
    }

    /**
     * Resumen del snapshot.
     */
    public function getResumenSnapshot()
    {
        return $this->detalle_tramites_snapshot['resumen'] ?? null;
    }

    /**
     * Dependencia del snapshot.
     */
    public function getDependenciaSnapshot()
    {
        return $this->detalle_tramites_snapshot['dependencia'] ?? null;
    }

    /**
     * Indica si el snapshot es válido.
     */
    public function hasValidSnapshot()
    {
        return !empty($this->detalle_tramites_snapshot) 
            && isset($this->detalle_tramites_snapshot['tramites'])
            && is_array($this->detalle_tramites_snapshot['tramites'])
            && count($this->detalle_tramites_snapshot['tramites']) > 0;
    }
}