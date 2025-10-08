<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('terrenos', function (Blueprint $table) {
            $table->unsignedBigInteger('idcuadra')->nullable()->after('idcategoria');
            $table->foreign('idcuadra')->references('id')->on('cuadras');
        });
    }

    public function down()
    {
        Schema::table('terrenos', function (Blueprint $table) {
            $table->dropForeign(['idcuadra']);
            $table->dropColumn('idcuadra');
        });
    }
};
