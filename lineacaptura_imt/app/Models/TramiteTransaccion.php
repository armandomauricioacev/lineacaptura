<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TramiteTransaccion extends Model
{
    use HasFactory;

    protected $table = 'tramite_transacciones';
    public $timestamps = false; // Le decimos a Laravel que esta tabla no tiene timestamps

    protected $fillable = [
        'tramite_id',
        'clave_transaccion',
    ];
}