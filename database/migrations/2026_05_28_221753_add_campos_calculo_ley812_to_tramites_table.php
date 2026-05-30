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
        Schema::table('tramites', function (Blueprint $table) {
            $table->decimal('tributo_actualizado', 14, 2)->default(0)->after('total_idtgb');
            $table->decimal('multa_idf', 12, 2)->default(0)->after('recargo_mora');
            $table->decimal('ufv_vencimiento', 8, 5)->nullable()->after('ufv_aplicada');
            $table->integer('dias_mora')->default(0)->after('ufv_vencimiento');
            $table->integer('categoria_tasa')->default(1)->after('dias_mora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropColumn(['tributo_actualizado', 'multa_idf', 'ufv_vencimiento', 'dias_mora', 'categoria_tasa']);
        });
    }
};
