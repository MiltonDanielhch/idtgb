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
        Schema::create('tasas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained(); // ← AÑADIR ESTO
            $table->foreignId('parentesco_id')->constrained('parentescos');
            $table->decimal('tasa', 5, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();

            // Añadir para evitar duplicados
            $table->unique(['departamento_id', 'parentesco_id', 'vigente_desde']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasas');
    }
};
