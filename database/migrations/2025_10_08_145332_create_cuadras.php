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
        Schema::create('cuadras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idbarrio');
            $table->string('nombre');
            $table->geometry('poligono', 'polygon')->nullable();
            $table->timestamps();

            $table->foreign('idbarrio')->references('id')->on('barrios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadras');
    }
};
