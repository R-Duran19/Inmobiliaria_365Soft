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
        Schema::create('seguimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('negocio_id');
            $table->string('tipo', 50);
            $table->text('descripcion');
            $table->dateTime('fecha_seguimiento');
            $table->date('proximo_seguimiento')->nullable();
            $table->boolean('recordatorio_enviado')->default(false);
            $table->unsignedBigInteger('asesor_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('negocio_id')
                  ->references('id')
                  ->on('negocios')
                  ->onDelete('cascade');

            $table->foreign('asesor_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            // Índices para optimización
            $table->index('negocio_id');
            $table->index('asesor_id');
            $table->index('fecha_seguimiento');
            $table->index('proximo_seguimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimientos');
    }
};