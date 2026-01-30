<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ufv;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UfvApiSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para obtener UFVs de una fuente externa (ej. BCB).
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Iniciando UfvApiSeeder: Obteniendo datos reales de UFV...');

        // Define el rango de fechas para obtener los datos.
        // Podrías obtener la última fecha registrada en tu DB y empezar desde ahí.
        $lastUfvDate = Ufv::orderByDesc('fecha')->first()?->fecha;
        // Si no hay UFVs, empieza desde una fecha razonable (ej. inicio del año anterior o una fecha específica).
        $startDate = $lastUfvDate ? $lastUfvDate->copy()->addDay() : Carbon::parse('2023-01-01');
        $endDate = now();

        // URL de la API del Banco Central de Bolivia (BCB) o un servicio similar.
        // NOTA IMPORTANTE: Esta URL es un EJEMPLO. Deberás encontrar la URL oficial y su formato.
        // Es posible que el BCB no ofrezca una API pública directa y necesites un scraper
        // o un servicio intermedio. Investiga la fuente oficial para obtener la URL y el formato correctos.
        $apiUrl = "https://api.bcb.gob.bo/ufv/range"; // URL de ejemplo
        $params = [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            // 'api_key' => env('BCB_API_KEY'), // Si la API requiere autenticación, usa una variable de entorno
        ];

        try {
            $response = Http::timeout(30)->get($apiUrl, $params);

            if ($response->successful()) {
                $ufvData = $response->json();

                if (empty($ufvData)) {
                    $this->command->warn('No se recibieron datos de UFV de la API.');
                    return;
                }

                $insertedCount = 0;
                foreach ($ufvData as $data) {
                    // Asegúrate de que el formato de la respuesta JSON coincida con 'fecha' y 'valor'
                    if (isset($data['fecha']) && isset($data['valor'])) {
                        Ufv::firstOrCreate(['fecha' => $data['fecha']], ['valor' => (float) $data['valor']]);
                        $insertedCount++;
                    }
                }
                $this->command->info("✅ Se insertaron/actualizaron {$insertedCount} valores UFV desde la API.");
            } else {
                $this->command->error("❌ Error al conectar con la API de UFVs: " . $response->status());
                Log::error("UfvApiSeeder: Error al obtener UFVs de la API. Status: " . $response->status() . " Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->command->error("❌ Excepción al obtener UFVs de la API: " . $e->getMessage());
            Log::error("UfvApiSeeder: Excepción al obtener UFVs de la API: " . $e->getMessage(), ['exception' => $e]);
        }
    }
}
