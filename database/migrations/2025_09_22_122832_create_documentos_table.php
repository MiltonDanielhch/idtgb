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
            $table->string('tipo_doc', 100);
            $table->string('archivo_path', 250);
            $table->char('hash_sha256', 64)->nullable();
            $table->foreignId('person_id')->nullable()->constrained('people');
            $table->text('descripcion')->nullable();
            $table->boolean('vigente')->default(true);
            $table->unsignedTinyInteger('version')->default(1);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
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
