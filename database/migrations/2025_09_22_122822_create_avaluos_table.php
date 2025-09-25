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
        Schema::create('avaluos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inmueble_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo_avaluo', ['Fiscal', 'Comercial', 'Pericial']);
            $table->date('fecha_avaluo');
            $table->decimal('valor', 14, 2);
            $table->foreignId('perito_id')->nullable()->constrained('people');
            $table->string('documento_path', 250)->nullable();
            $table->enum('estado', ['Vigente', 'Caducado'])->default('Vigente');
            $table->timestamps();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaluos');
    }
};
