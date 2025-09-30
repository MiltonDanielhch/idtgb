<?php

namespace Database\Seeders;

use App\Models\Provincia;
use App\Models\Municipio;
use Illuminate\Database\Seeder;

class MunicipioSeeder extends Seeder
{
    public function run(): void
    {
        // Cercado
        $cercado = Provincia::where('nombre', 'Cercado')->firstOrFail();
        foreach (['Trinidad', 'San Javier', 'San Pedro'] as $m) {
            $cercado->municipios()->firstOrCreate(['nombre' => $m]);
        }

        // Moxos
        $moxos = Provincia::where('nombre', 'Moxos')->firstOrFail();
        foreach (['San Ignacio de Moxos', 'Santa Ana del Yacuma', 'Exaltación'] as $m) {
            $moxos->municipios()->firstOrCreate(['nombre' => $m]);
        }

        // Yacuma
        $yacuma = Provincia::where('nombre', 'Yacuma')->firstOrFail();
        foreach (['Santa Ana del Yacuma', 'Reyes', 'Rurrenabaque'] as $m) {
            $yacuma->municipios()->firstOrCreate(['nombre' => $m]);
        }

        // Marbán
        $marban = Provincia::where('nombre', 'Marbán')->firstOrFail();
        foreach (['Loreto', 'San Andrés', 'San Ignacio de Moxos'] as $m) {
            $marban->municipios()->firstOrCreate(['nombre' => $m]);
        }

        // Mamoré
        $mam = Provincia::where('nombre', 'Mamoré')->firstOrFail();
        foreach (['San Joaquín', 'Baures', 'El Carmen'] as $m) {
            $mam->municipios()->firstOrCreate(['nombre' => $m]);
        }

        // Iténez
        $ite = Provincia::where('nombre', 'Iténez')->firstOrFail();
        foreach (['Magdalena', 'Huacaraje', 'El Carmen'] as $m) {
            $ite->municipios()->firstOrCreate(['nombre' => $m]);
        }

        // Ballivián
        $bal = Provincia::where('nombre', 'Ballivián')->firstOrFail();
        foreach (['San Borja', 'Rurrenabaque', 'Santa Rosa'] as $m) {
            $bal->municipios()->firstOrCreate(['nombre' => $m]);
        }

        // Vaca Díez
        $vd = Provincia::where('nombre', 'Vaca Díez')->firstOrFail();
        foreach (['Guayaramerín', 'Riberalta', 'Filadelfia'] as $m) {
            $vd->municipios()->firstOrCreate(['nombre' => $m]);
        }
    }
}
