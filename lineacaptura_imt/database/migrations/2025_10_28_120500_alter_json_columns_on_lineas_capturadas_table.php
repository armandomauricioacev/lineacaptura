<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lineas_capturadas', function (Blueprint $table) {
            // Ampliar columnas para evitar truncamientos de JSON grandes
            $table->longText('json_generado')->nullable()->change();
            $table->longText('json_recibido')->nullable()->change();
            $table->longText('detalle_tramites_snapshot')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lineas_capturadas', function (Blueprint $table) {
            // Revertir a text estándar (puede truncar si el JSON es muy grande)
            $table->text('json_generado')->nullable()->change();
            $table->text('json_recibido')->nullable()->change();
            $table->text('detalle_tramites_snapshot')->nullable()->change();
        });
    }
};