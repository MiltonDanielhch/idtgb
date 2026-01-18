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
        Schema::table('tasas', function (Blueprint $table) {
            $table->index(['departamento_id', 'parentesco_id', 'tipo_transmision_id', 'vigente_desde'], 'tasas_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasas', function (Blueprint $table) {
            $table->dropIndex('tasas_composite_index');
        });
    }
};
