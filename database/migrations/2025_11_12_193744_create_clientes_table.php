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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30);
            $table->string('apellido_materno', 30)->nullable();
            $table->string('apellido_paterno', 30);
            $table->string('telefono', 20)->nullable();
            $table->string('telefono_referencia', 20)->nullable();  
            $table->string('direccion', 150)->nullable();
            $table->string('ci', 20);
            $table->string('codigo_carnet', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
