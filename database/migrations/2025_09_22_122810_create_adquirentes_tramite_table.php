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
        Schema::create('adquirentes_tramite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tramite_id')->constrained()->cascadeOnDelete();
            $table->foreignId('persona_id')->constrained('people');
            $table->foreignId('parentesco_id')->constrained('parentescos');
            $table->decimal('tasa_aplicada', 5, 2); // ← CAMBIAR: en lugar de tasa_id
            $table->decimal('porcentaje', 5, 2);
            $table->decimal('idtgb_proporcional', 12, 2);
            $table->boolean('es_beneficiario_exencion')->default(false);
            $table->string('documento_sustento_exencion', 250)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adquirentes_tramite');
    }
};
