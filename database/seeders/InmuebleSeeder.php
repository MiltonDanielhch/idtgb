<?php

namespace Database\Seeders;

use App\Models\Inmueble;
use App\Models\Municipio;
use App\Models\TipoInmueble;
use Illuminate\Database\Seeder;

class InmuebleSeeder extends Seeder
{
    public function run(): void
    {
        $inmuebles = [
            // === TRINIDAD (Cercado) ===
            [
                'catastro' => '12-3456-01-0101',
                'municipio' => 'Trinidad',
                'tipo' => 'Urbana',
                'barrio_comunidad' => 'Barrio 24 de Septiembre',
                'direccion' => 'Calle Avaroa #123',
                'superficie_m2' => 250.00,
                'valor_catastral' => 500000.00,
                'matricula_rr' => 'RR-123456-2025',
                'es_vivienda_unica_familiar' => true,
            ],
            [
                'catastro' => '12-7890-01-0202',
                'municipio' => 'Trinidad',
                'tipo' => 'Urbana',
                'barrio_comunidad' => 'Barrio San Miguel',
                'direccion' => 'Av. América #456',
                'superficie_m2' => 180.00,
                'valor_catastral' => 380000.00,
                'matricula_rr' => 'RR-789012-2024',
                'es_vivienda_unica_familiar' => false,
            ],

            // === RIBERALTA (Vaca Díez) ===
            [
                'catastro' => '13-1122-02-0303',
                'municipio' => 'Riberalta',
                'tipo' => 'Urbana',
                'barrio_comunidad' => 'Barrio El Carmen',
                'direccion' => 'Calle Sucre #789',
                'superficie_m2' => 200.00,
                'valor_catastral' => 420000.00,
                'matricula_rr' => 'RR-334455-2025',
                'es_vivienda_unica_familiar' => true,
            ],

            // === SAN IGNACIO DE MOXOS (Moxos) ===
            [
                'catastro' => '14-3344-03-0404',
                'municipio' => 'San Ignacio de Moxos',
                'tipo' => 'Mixta',
                'barrio_comunidad' => 'Comunidad Indígena TIPNIS',
                'direccion' => 'Zona rural, camino a Trinidad',
                'superficie_m2' => 5000.00,
                'valor_catastral' => 150000.00,
                'matricula_rr' => 'RR-556677-2023',
                'es_vivienda_unica_familiar' => true,
            ],

            // === SANTA ANA DEL YACUMA (Yacuma) ===
            [
                'catastro' => '15-5566-04-0505',
                'municipio' => 'Santa Ana del Yacuma',
                'tipo' => 'Rural',
                'barrio_comunidad' => 'Comunidad Ganadera El Paraíso',
                'direccion' => 'Finca a 10 km de la capital',
                'superficie_m2' => 100000.00,
                'valor_catastral' => 200000.00,
                'matricula_rr' => 'RR-889900-2024',
                'es_vivienda_unica_familiar' => false,
            ],

            // === SAN BORJA (José Ballivián) ===
            [
                'catastro' => '16-7788-05-0606',
                'municipio' => 'San Borja',
                'tipo' => 'Urbana',
                'barrio_comunidad' => 'Barrio San Antonio',
                'direccion' => 'Calle Bolívar #321',
                'superficie_m2' => 220.00,
                'valor_catastral' => 450000.00,
                'matricula_rr' => 'RR-112233-2025',
                'es_vivienda_unica_familiar' => true,
            ],
        ];

        foreach ($inmuebles as $data) {
            $municipio = Municipio::where('nombre', $data['municipio'])->first();
            $tipo = TipoInmueble::where('nombre', $data['tipo'])->first();

            if ($municipio && $tipo) {
                Inmueble::firstOrCreate(
                    ['catastro' => $data['catastro']],
                    [
                        'tipo_inmueble_id' => $tipo->id,
                        'municipio_id' => $municipio->id,
                        'barrio_comunidad' => $data['barrio_comunidad'],
                        'direccion' => $data['direccion'],
                        'superficie_m2' => $data['superficie_m2'],
                        'valor_catastral' => $data['valor_catastral'],
                        'matricula_rr' => $data['matricula_rr'],
                        'es_vivienda_unica_familiar' => $data['es_vivienda_unica_familiar'],
                        'estado_inmueble' => 'Activo',
                    ]
                );
            }
        }
    }
}
