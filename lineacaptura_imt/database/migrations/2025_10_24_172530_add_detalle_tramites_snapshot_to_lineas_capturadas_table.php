<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lineas_capturadas', function (Blueprint $table) {
            // Verificar si la columna ya existe antes de agregarla
            if (!Schema::hasColumn('lineas_capturadas', 'detalle_tramites_snapshot')) {
                // Agregar columna JSON para el snapshot de trámites
                // Permite valores NULL en caso de registros antiguos
                $table->json('detalle_tramites_snapshot')->nullable()->after('tramite_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lineas_capturadas', function (Blueprint $table) {
            // Solo eliminar si existe
            if (Schema::hasColumn('lineas_capturadas', 'detalle_tramites_snapshot')) {
                $table->dropColumn('detalle_tramites_snapshot');
            }
        });
    }
};