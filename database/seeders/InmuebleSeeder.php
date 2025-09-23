<?php

namespace Database\Seeders;

use App\Models\Inmueble;
use App\Models\Municipio;
use App\Models\TipoInmueble;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InmuebleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $municipio = Municipio::where('nombre', 'Trinidad')->first()->id;
        $tipo      = TipoInmueble::where('nombre', 'Urbana')->first()->id;

        Inmueble::create([
            'catastro'                   => '12-3456-01-0101',
            'complemento'                => null,
            'tipo_inmueble_id'           => $tipo,
            'municipio_id'               => $municipio,
            'barrio_comunidad'           => 'Barrio 24 de Septiembre',
            'direccion'                  => 'Calle Avaroa # 123',
            'superficie_m2'              => 250.00,
            'valor_catastral'            => 500000.00,
            'matricula_rr'               => 'RR-123456-2025',
            'es_vivienda_unica_familiar' => true,
        ]);
    }
}
