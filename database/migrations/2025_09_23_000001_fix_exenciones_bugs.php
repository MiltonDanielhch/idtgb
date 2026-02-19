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
        // Agregar índice único para prevenir duplicados (Bug #1)
        Schema::table('tramite_exenciones', function (Blueprint $table) {
            $table->unique(['tramite_id', 'exencion_id'], 'unique_tramite_exencion');
        });

        // Agregar soft deletes a exenciones (Bug #8)
        Schema::table('exenciones', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Agregar soft deletes a tramite_exenciones (Bug #8)
        Schema::table('tramite_exenciones', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tramite_exenciones', function (Blueprint $table) {
            $table->dropUnique('unique_tramite_exencion');
            $table->dropSoftDeletes();
        });

        Schema::table('exenciones', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
