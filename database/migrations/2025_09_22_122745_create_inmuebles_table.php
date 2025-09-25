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
        Schema::create('inmuebles', function (Blueprint $table) {
            $table->id();
            $table->string('complemento', 3)->nullable();
            $table->string('catastro', 15)->unique();
            $table->foreignId('tipo_inmueble_id')->constrained('tipos_inmueble');
            $table->foreignId('municipio_id')->nullable()->constrained();
            $table->string('barrio_comunidad', 100)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->decimal('superficie_m2', 12, 2)->nullable();
            $table->decimal('valor_catastral', 14, 2);
            $table->string('matricula_rr', 20)->nullable();
            $table->boolean('es_vivienda_unica_familiar')->default(false);
            $table->enum('estado_inmueble', ['Activo', 'Transferido', 'Baja'])->default('Activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmuebles');
    }
};
