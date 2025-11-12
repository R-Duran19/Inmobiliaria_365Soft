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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('idusuario');
            $table->unsignedBigInteger('idcliente');
            $table->unsignedBigInteger('idterreno');
            $table->decimal('precio_lista', 10, 2);
            $table->decimal('descuento', 10, 2);
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('cuota_inicial', 10, 2);
            $table->decimal('total_plan_pago', 10, 2);  


            $table->foreign('idusuario')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('idcliente')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('idterreno')->references('id')->on('terrenos')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
