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
        Schema::table('provincias', function (Blueprint $table) {
            $table->unique(['nombre', 'departamento_id']);
        });

        Schema::table('municipios', function (Blueprint $table) {
            $table->unique(['nombre', 'provincia_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provincias', function (Blueprint $table) {
            $table->dropUnique(['nombre', 'departamento_id']);
        });

        Schema::table('municipios', function (Blueprint $table) {
            $table->dropUnique(['nombre', 'provincia_id']);
        });
    }
};
