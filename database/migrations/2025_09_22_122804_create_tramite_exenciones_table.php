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
        Schema::create('tramite_exenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tramite_id')->constrained();
            $table->foreignId('exencion_id')->constrained();
            $table->decimal('monto_aplicado', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tramite_exenciones');
    }
};
