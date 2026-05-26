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
        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign(['municipio_id']);
            $table->dropColumn(['birth_date', 'gender', 'email', 'address', 'municipio_id', 'status', 'estado_persona']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('legal_name');
            $table->string('email')->nullable()->after('birth_date');
            $table->text('address')->nullable()->after('phone');
            $table->foreignId('municipio_id')->nullable()->constrained()->after('address');
            $table->enum('gender', ['Masculino', 'Femenino'])->nullable()->after('municipio_id');
            $table->tinyInteger('status')->default(1)->comment('1=activo,0=inactivo,2=pending')->after('image');
            $table->enum('estado_persona', ['Activo', 'Inactivo', 'Fallecido'])->default('Activo')->after('status');
        });
    }
};
