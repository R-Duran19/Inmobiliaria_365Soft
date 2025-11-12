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
        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
           $table->unsignedBigInteger('idventa');

            $table->integer('nro_cuota');
            $table->date('fecha_a_pagar');
            $table->decimal('valor_cuota', 10, 2);
            $table->decimal('saldo', 10, 2);
            $table->timestamp('fecha_pago')->nullable(); 

            $table->tinyInteger('estado')->default(0);
            $table->decimal('mora', 5, 2)->default(0);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuotas');
    }
};
