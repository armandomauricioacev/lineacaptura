<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dependencia extends Model
{
    // Definir la tabla asociada
    protected $table = 'dependencias';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre', 'clave_dependencia', 'unidad_administrativa'
    ];

    // Campos de fechas
    public $timestamps = true;

    /**
     * Relación: Dependencia tiene muchos trámites.
     */
    public function tramites()
    {
        return $this->hasMany(Tramite::class, 'clave_dependencia_siglas', 'clave_dependencia');
    }
}
