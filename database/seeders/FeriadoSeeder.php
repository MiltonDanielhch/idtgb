<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Feriado;
use App\Models\Departamento;

class FeriadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Feriado::withTrashed()->forceDelete();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Obtener departamento Beni
        $beni = Departamento::where('codigo', 'BE')->first();
        $beniId = $beni ? $beni->id : null;

        // Feriados Nacionales Oficiales Bolivia 2026
        $feriadosNacionales = [
            ['fecha' => '2026-01-01', 'nombre' => 'Año Nuevo', 'tipo' => 'Nacional'],
            ['fecha' => '2026-01-23', 'nombre' => 'Día del Estado Plurinacional (Trasladado)', 'tipo' => 'Nacional'],
            ['fecha' => '2026-02-16', 'nombre' => 'Carnaval (Lunes)', 'tipo' => 'Nacional'],
            ['fecha' => '2026-02-17', 'nombre' => 'Carnaval (Martes)', 'tipo' => 'Nacional'],
            ['fecha' => '2026-04-03', 'nombre' => 'Viernes Santo', 'tipo' => 'Nacional'],
            ['fecha' => '2026-05-01', 'nombre' => 'Día del Trabajador', 'tipo' => 'Nacional'],
            ['fecha' => '2026-06-04', 'nombre' => 'Corpus Christi', 'tipo' => 'Nacional'],
            ['fecha' => '2026-06-05', 'nombre' => 'Feriado Adicional (Puente Corpus Christi)', 'tipo' => 'Nacional'],
            ['fecha' => '2026-06-22', 'nombre' => 'Año Nuevo Andino Amazónico Chaqueño (Trasladado)', 'tipo' => 'Nacional'],
            ['fecha' => '2026-08-06', 'nombre' => 'Día de la Independencia de Bolivia', 'tipo' => 'Nacional'],
            ['fecha' => '2026-08-07', 'nombre' => 'Feriado Adicional (Puente Independencia)', 'tipo' => 'Nacional'],
            ['fecha' => '2026-11-02', 'nombre' => 'Día de Todos los Difuntos', 'tipo' => 'Nacional'],
            ['fecha' => '2026-12-25', 'nombre' => 'Navidad', 'tipo' => 'Nacional'],
        ];

        foreach ($feriadosNacionales as $feriado) {
            Feriado::create([
                'fecha' => $feriado['fecha'],
                'nombre' => $feriado['nombre'],
                'departamento_id' => null, // Nacional
                'tipo' => $feriado['tipo'],
                'activo' => true,
            ]);
        }

        // Feriados Departamentales y Locales de Beni 2026
        if ($beniId) {
            $feriadosBeni = [
                // La Chope Piesta (Santísima Trinidad) es movible, en 2026 la fiesta principal cae a finales de mayo (Feriado local en Trinidad)
                ['fecha' => '2026-05-25', 'nombre' => 'Chope Piesta - Santísima Trinidad (Feriado Local)', 'tipo' => 'Departamental'], 
                // Aniversario de la Revolución del Beni
                ['fecha' => '2026-11-18', 'nombre' => 'Aniversario del Departamento del Beni', 'tipo' => 'Departamental'],
            ];

            foreach ($feriadosBeni as $feriado) {
                Feriado::create([
                    'fecha' => $feriado['fecha'],
                    'nombre' => $feriado['nombre'],
                    'departamento_id' => $beniId,
                    'tipo' => $feriado['tipo'],
                    'activo' => true,
                ]);
            }
        }

        $this->command->info('Feriados nacionales y de Beni para la gestión 2026 cargados exitosamente.');
    }
}