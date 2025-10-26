<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IdtgbMaestrosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Base de Voyager (incluye usuarios, roles, permisos básicos)
            VoyagerDatabaseSeeder::class,

            // 2. Geografía
            DepartamentoSeeder::class,
            ProvinciaSeeder::class,
            MunicipioSeeder::class,

            // Maestros del sistema
            ParentescoSeeder::class,
            TipoTransmisionSeeder::class,
            TipoInmuebleSeeder::class,
            TasaSeeder::class,
            ExencionSeeder::class,

            // 4. Datos operativos y de prueba
            PeopleBeniSeeder::class,
            InmuebleSeeder::class,
            AvaluoSeeder::class,

            // 5. Trámites y sus relaciones
            TramiteSeeder::class,
            AdquirenteTramiteSeeder::class,
            DisponenteTramiteSeeder::class,
            TramiteExencionSeeder::class,
            DocumentoSeeder::class,
            PagoSeeder::class,

            // 6. Componentes finales (Menús y UFV)
            IdtgbMenuAppendSeeder::class, // <-- AÑADIR ESTE SEEDER
            UfvSeeder::class,
        ]);
    }
}
