<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documentos_terreno', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idterreno');
            $table->string('nombre_documento');
            $table->timestamps();

            $table->foreign('idterreno')->references('id')->on('terrenos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_terreno');
    }
};
