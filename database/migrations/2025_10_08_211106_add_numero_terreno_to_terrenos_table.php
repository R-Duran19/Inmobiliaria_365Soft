<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('terrenos', function (Blueprint $table) {
            $table->unsignedBigInteger('numero_terreno')->nullable()->after('ubicacion');
        });
    }

    public function down()
    {
        Schema::table('terrenos', function (Blueprint $table) {
            $table->dropColumn('numero_terreno');
        });
    }
};
