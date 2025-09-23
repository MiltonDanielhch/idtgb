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
        Schema::create('tramites', function (Blueprint $table) {
            $table->id();
            $table->string('nro_tramite', 15)->unique();
            $table->date('fecha_presentacion');
            $table->foreignId('tipo_transmision_id')->constrained('tipos_transmision');
            $table->foreignId('inmueble_id')->constrained();
            $table->decimal('valor_declarado', 14, 2);
            $table->decimal('base_imponible', 14, 2);
            $table->decimal('total_idtgb', 12, 2)->default(0);
            $table->decimal('recargo_mora', 12, 2)->default(0);
            $table->decimal('monto_final', 14, 2)->default(0);
            $table->enum('estado', ['Borrador', 'Pagado', 'Observado', 'Anulado', 'Finalizado'])->default('Borrador');
            $table->date('fecha_transmision'); // ← AÑADIR ESTA FECHA CLAVE
            $table->date('fecha_vencimiento');
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tramites');
    }
};
