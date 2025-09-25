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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tramite_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo_doc', ['Escritura', 'Testamento', 'Partida', 'CI', 'Avaluo', 'Poder', 'Otro']);
            $table->string('file_path', 250);
            $table->char('hash_sha256', 64)->nullable();
            $table->foreignId('persona_id')->nullable()->constrained('people');
            $table->boolean('vigente')->default(true);
            $table->unsignedTinyInteger('version')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
