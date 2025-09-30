<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IdtgbMaestrosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartamentoSeeder::class,
            ProvinciaSeeder::class,
            MunicipioSeeder::class,

            ParentescoSeeder::class,
            TipoTransmisionSeeder::class,
            TipoInmuebleSeeder::class,
            TasaSeeder::class,
            ExencionSeeder::class,

            InmuebleSeeder::class,

            TramiteSeeder::class,
            TramiteExencionSeeder::class,
            AdquirenteTramiteSeeder::class,
            DisponenteTramiteSeeder::class,
            AvaluoSeeder::class,
            DocumentoSeeder::class,

            PagoSeeder::class,
        ]);
    }
}
