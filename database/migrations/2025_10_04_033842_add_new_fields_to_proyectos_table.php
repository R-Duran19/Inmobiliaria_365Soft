<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->date('fecha_lanzamiento')->nullable()->after('descripcion');
            $table->integer('numero_lotes')->nullable()->after('fecha_lanzamiento');
            $table->string('ubicacion')->nullable()->after('numero_lotes');
            $table->string('fotografia')->nullable()->after('ubicacion');
        });
    }

    public function down()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn(['fecha_lanzamiento', 'numero_lotes', 'ubicacion', 'fotografia']);
        });
    }
};
