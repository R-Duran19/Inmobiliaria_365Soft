<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_terreno', function (Blueprint $table) {
            $table->text('texto_extraido')->nullable()->after('nombre_documento');
            $table->json('datos_extraidos')->nullable()->after('texto_extraido');
            $table->enum('estado_ocr', ['pendiente', 'procesado', 'error'])
                  ->default('pendiente')
                  ->after('datos_extraidos');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_terreno', function (Blueprint $table) {
            $table->dropColumn(['texto_extraido', 'datos_extraidos', 'estado_ocr']);
        });
    }
};