<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categorias_terrenos', function (Blueprint $table) {
            $table->string('color', 7)->default('#000000')->after('estado');
        });
    }

    public function down()
    {
        Schema::table('categorias_terrenos', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
