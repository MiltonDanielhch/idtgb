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
        // Índices para la tabla inmuebles
        Schema::table('inmuebles', function (Blueprint $table) {
            $table->index(['estado_inmueble', 'tipo_inmueble_id'], 'idx_estado_tipo');
            $table->index('municipio_id', 'idx_municipio');
            $table->index('catastro', 'idx_catastro');
            $table->index('matricula_rr', 'idx_matricula_rr');
        });

        // Índices para la tabla tramite_inmuebles
        Schema::table('tramite_inmuebles', function (Blueprint $table) {
            $table->index(['tramite_id', 'inmueble_id'], 'idx_tramite_inmueble');
            $table->index('inmueble_id', 'idx_inmueble');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Índices de la tabla inmuebles
        Schema::table('inmuebles', function (Blueprint $table) {
            $table->dropIndex('idx_estado_tipo');
            $table->dropIndex('idx_municipio');
            $table->dropIndex('idx_catastro');
            $table->dropIndex('idx_matricula_rr');
        });

        // Índices de la tabla tramite_inmuebles
        Schema::table('tramite_inmuebles', function (Blueprint $table) {
            $table->dropIndex('idx_tramite_inmueble');
            $table->dropIndex('idx_inmueble');
            $table->dropIndex('idx_created_at');
        });
    }
};
