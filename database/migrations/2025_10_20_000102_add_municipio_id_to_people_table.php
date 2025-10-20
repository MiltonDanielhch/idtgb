<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('municipio_id')->nullable()->constrained()->after('address');
        });
    }

    public function down()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign(['municipio_id']);
            $table->dropColumn('municipio_id');
        });
    }
};
