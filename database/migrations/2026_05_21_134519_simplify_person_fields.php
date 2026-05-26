<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Paso 1: Agregar campo nombre_completo como nullable
        Schema::table('people', function (Blueprint $table) {
            $table->string('nombre_completo')->nullable()->after('legal_name');
        });

        // Paso 2: Migrar datos existentes de los campos antiguos a nombre_completo
        DB::statement("
            UPDATE people 
            SET nombre_completo = CONCAT(
                COALESCE(first_name, ''), 
                CASE WHEN middle_name IS NOT NULL AND middle_name != '' THEN ' ' ELSE '' END,
                COALESCE(middle_name, ''),
                CASE WHEN paternal_surname IS NOT NULL AND paternal_surname != '' THEN ' ' ELSE '' END,
                COALESCE(paternal_surname, ''),
                CASE WHEN maternal_surname IS NOT NULL AND maternal_surname != '' THEN ' ' ELSE '' END,
                COALESCE(maternal_surname, '')
            )
            WHERE nombre_completo IS NULL OR nombre_completo = ''
        ");

        // Paso 3: Eliminar campos de nombre individuales y fotografía
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'paternal_surname', 'maternal_surname', 'image']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // Eliminar campo nombre_completo
            $table->dropColumn('nombre_completo');
            
            // Restaurar campos de nombre individuales y fotografía
            $table->string('first_name')->nullable()->after('legal_name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('paternal_surname')->nullable()->after('middle_name');
            $table->string('maternal_surname')->nullable()->after('paternal_surname');
            $table->string('image')->nullable()->after('phone');
        });
    }
};
