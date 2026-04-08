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
        Schema::table('parentescos', function (Blueprint $table) {
            $table->tinyInteger('categoria_tasa')->nullable()->after('nombre');
            $table->index('categoria_tasa');
        });
    }

    public function down(): void
    {
        Schema::table('parentescos', function (Blueprint $table) {
            $table->dropIndex(['categoria_tasa']);
            $table->dropColumn('categoria_tasa');
        });
    }
};
