<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IdtgbMaestrosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,
            // Geografía
            DepartamentoSeeder::class,
            ProvinciaSeeder::class,
            MunicipioSeeder::class,

            // Maestros del sistema
            ParentescoSeeder::class,
            TipoTransmisionSeeder::class,
            TipoInmuebleSeeder::class,
            TasaSeeder::class,
            PeopleBeniSeeder::class,    // ⚠️ ¡FALTABA ESTE!
            ExencionSeeder::class,

            // Datos operativos
            InmuebleSeeder::class,
            AvaluoSeeder::class,

            // Trámites y sus relaciones
            TramiteSeeder::class,
            AdquirenteTramiteSeeder::class,
            DisponenteTramiteSeeder::class,
            TramiteExencionSeeder::class,
            DocumentoSeeder::class,
            PagoSeeder::class,

            // 🔑 Crítico para cálculos
            UfvSeeder::class,           // ⚠️ ¡FALTABA ESTE!
        ]);
    }
}
