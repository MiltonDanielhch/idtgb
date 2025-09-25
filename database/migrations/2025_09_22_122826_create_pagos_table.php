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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tramite_id')->constrained()->cascadeOnDelete();
            $table->dateTime('fecha_pago');
            $table->decimal('monto', 14, 2);
            $table->string('codigo_barras', 50)->nullable();
            $table->string('nro_operacion', 25)->nullable();
            $table->timestamp('conciliado_el')->nullable();
            $table->string('banco', 30)->nullable();
            $table->enum('estado', ['Pendiente', 'Aplicado', 'Reversado'])->default('Pendiente');
            $table->timestamps();

            //  Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
