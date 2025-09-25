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
            $table->foreignId('departamento_id')->constrained();
            $table->foreignId('parentesco_id')->constrained('parentescos');
            $table->foreignId('tipo_transmision_id')->nullable()->constrained('tipos_transmision');
            $table->decimal('tasa', 5, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->timestamps();

            $table->unique(
                ['departamento_id', 'parentesco_id', 'tipo_transmision_id', 'vigente_desde'],
                'uq_tasas_dep_par_trans_vig'   // ≤ 64 caracteres
            );
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
