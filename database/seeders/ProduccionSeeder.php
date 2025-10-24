<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProduccionSeeder extends Seeder
{
    /**
     * Ejecuta los seeders esenciales para un entorno de producción.
     *
     * @return void
     */
    public function run()
    {
        // Seeders para la funcionalidad principal de Voyager
        $this->call(VoyagerDatabaseSeeder::class);

        // Seeder para usuarios iniciales (asegúrate de que sea seguro para producción)
        $this->call(UsersTableSeeder::class);

        // Seeders para los datos maestros de la aplicación (datos estáticos y necesarios)
        $this->call([
            DepartamentoSeeder::class,
            ProvinciaSeeder::class,
            MunicipioSeeder::class,
            ParentescoSeeder::class,
            TipoTransmisionSeeder::class,
            TipoInmuebleSeeder::class,
            TasaSeeder::class,
            ExencionSeeder::class,
            // UfvSeeder::class, // Carga los valores iniciales de UFV
            ExencionSeeder::class,
            // UfvApiSeeder::class, // Carga los valores reales de UFV desde una API (para producción)
            IdtgbMenuAppendSeeder::class, // Añade los menús personalizados a Voyager
        ]);
    }
}
