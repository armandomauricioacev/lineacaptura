<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineasCapturadas extends Model
{
    use HasFactory;

    protected $table = 'lineas_capturadas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        'estado_pago',
        'fecha_solicitud',
        'fecha_vigencia',

        // ==========================================================
        //  AÑADIR ESTOS CAMPOS PARA PERMITIR EL GUARDADO MASIVO
        // ==========================================================
        'solicitud',
        'importe_cuota',
        'importe_iva',
        'importe_total',
        'json_generado',

        // ==========================================================
        //  CAMPOS ADICIONALES DEL SAT
        // ==========================================================
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
        'procesado_exitosamente',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_vigencia' => 'date',
        'fecha_vigencia_sat' => 'date',
        'fecha_respuesta_sat' => 'datetime',
        'json_generado' => 'array',
        'json_recibido' => 'array',
        'errores_sat' => 'array',
        'procesado_exitosamente' => 'boolean',
    ];

    // Relaciones
    public function dependencia()
    {
        return $this->belongsTo(Dependencia::class);
    }

    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }
}