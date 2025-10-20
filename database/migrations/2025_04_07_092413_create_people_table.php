<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->enum('person_type', ['Natural', 'Jurídica'])->default('Natural');

            $table->string('tipo_doc', 10)->default('CI');
            $table->string('ci')->nullable();
            $table->string('ci_complemento', 5)->nullable();
            $table->string('nit')->nullable();

            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('paternal_surname')->nullable();
            $table->string('maternal_surname')->nullable();
            $table->string('legal_name')->nullable();

            $table->date('birth_date')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('municipio_id')->nullable()->constrained();

            $table->enum('gender', ['Masculino', 'Femenino'])->nullable();
            $table->string('image')->nullable();

            $table->tinyInteger('status')->default(1)->comment('1=activo,0=inactivo,2=pending');
            $table->enum('estado_persona', ['Activo', 'Inactivo', 'Fallecido'])->default('Activo');

            $table->timestamps();
            $table->foreignId('registerUser_id')->nullable()->constrained('users');
            $table->string('registerRole')->nullable();
            $table->softDeletes();
            $table->foreignId('deleteUser_id')->nullable()->constrained('users');
            $table->string('deleteRole')->nullable();
            $table->text('deleteObservation')->nullable();

            $table->unique(['ci', 'ci_complemento']);
            $table->unique(['nit']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('people');
    }
};
