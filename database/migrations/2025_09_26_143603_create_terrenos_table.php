<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terrenos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idproyecto');
            $table->string('ubicacion');
            $table->string('categoria');
            $table->string('superficie');
            $table->decimal('cuota_inicial', 10, 2);
            $table->decimal('cuota_mensual', 10, 2);
            $table->decimal('precio_venta', 10, 2);
            $table->integer('estado')->default(0); 
            $table->boolean('condicion')->default(true);            
            $table->geometry('poligono', 'polygon')->nullable();            
            $table->timestamps();

            $table->foreign('idproyecto')->references('id')->on('proyectos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terrenos');
    }
};