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
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('terreno_id');
            $table->string('tipo_operacion', 50)->default('ventas');
            $table->string('embudo', 50)->default('ventas');
            $table->string('etapa', 100);
            $table->date('fecha_inicio');
            $table->decimal('monto_estimado', 12, 2)->nullable();
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('asesor_id');
            $table->boolean('convertido_cliente')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('lead_id')
                  ->references('id')
                  ->on('leads')
                  ->onDelete('cascade');

            $table->foreign('terreno_id')
                  ->references('id')
                  ->on('terrenos')
                  ->onDelete('restrict');

            $table->foreign('asesor_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');

            // Índices para optimización
            $table->index('lead_id');
            $table->index('terreno_id');
            $table->index('asesor_id');
            $table->index('etapa');
            $table->index('fecha_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};