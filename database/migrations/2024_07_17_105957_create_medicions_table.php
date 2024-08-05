<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMedicionsTable extends Migration
{
    public function up()
    {
        Schema::create('mediciones', function (Blueprint $table) {
            $table->id();
            $table->integer('nro_cuenta');
            $table->integer('ruta');
            $table->integer('orden');
            $table->float('medicion');
            $table->float('consumo')->nullable();
            $table->date('fecha');
            $table->string('foto_medidor');
            $table->unsignedBigInteger('estado_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mediciones');
    }
}