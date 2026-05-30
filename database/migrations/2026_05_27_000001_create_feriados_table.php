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
        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre', 100);
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->onDelete('set null');
            $table->enum('tipo', ['Nacional', 'Departamental', 'Municipal'])->default('Nacional');
            $table->boolean('activo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimizar consultas
            $table->index(['fecha', 'activo']);
            $table->index(['departamento_id', 'activo']);
            $table->index('tipo');
            
            // Índice único para evitar duplicados de feriados
            $table->unique(['fecha', 'departamento_id'], 'feriados_fecha_departamento_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feriados');
    }
};
