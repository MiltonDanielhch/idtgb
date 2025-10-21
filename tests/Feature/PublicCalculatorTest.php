<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\Tasa;
use App\Models\TipoTransmision;
use Carbon\Carbon;
use Database\Seeders\DepartamentoSeeder;
use Database\Seeders\ParentescoSeeder;
use Database\Seeders\TipoTransmisionSeeder;
use Database\Seeders\TasaSeeder; // <-- Importar el nuevo seeder

class PublicCalculatorTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    private Departamento $departamento;
    private Parentesco $parentescoHijo;
    private TipoTransmision $tipoTransmisionDonacion;

    /**
     * Prepara el entorno de prueba antes de cada test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Cargar los datos maestros necesarios para los cálculos
        $this->seed(DepartamentoSeeder::class);
        $this->seed(ParentescoSeeder::class);
        $this->seed(TipoTransmisionSeeder::class);
        $this->seed(TasaSeeder::class); // <-- AÑADIDO: Cargar las tasas reales

        // Obtener los modelos que usaremos en las pruebas
        $this->departamento = Departamento::where('codigo', 'BE')->firstOrFail();
        $this->parentescoHijo = Parentesco::where('nombre', 'Hijo/a')->firstOrFail();
        $this->tipoTransmisionDonacion = TipoTransmision::where('nombre', 'Donación')->firstOrFail();

        // ELIMINADO: Ya no necesitamos crear una tasa de prueba manualmente,
        // ya que el TasaSeeder crea los datos reales que vamos a probar.
    }

    /**
     * @test
     * Simula una llamada AJAX al endpoint de la calculadora y verifica la respuesta JSON
     * usando los datos reales del TasaSeeder.
     */
    public function calculadora_publica_api_calcula_y_devuelve_json_correcto_con_datos_reales()
    {
        // Arrange: 2. FIJAMOS LA FECHA ACTUAL PARA EL TEST
        $fechaFija = Carbon::create(2023, 10, 26); // Una fecha en el pasado
        Carbon::setTestNow($fechaFija);

        $url = route('calculadora.beni.calcular');

        $formData = [
            'base_imponible'     => 100000,
            'parentesco_id'      => $this->parentescoHijo->id,
            'fecha_transmision'  => $fechaFija->toDateString(), // 3. Usamos la fecha fija
            'tipo_contribuyente' => 'Natural',
            'tipo_transmision'   => 'Donación',
        ];

        // Act: Hacemos la petición POST, esperando una respuesta JSON
        $response = $this->postJson($url, $formData);

        // Assert: Verificamos la respuesta
        $response->assertStatus(200);
        $response->assertJsonStructure([
            // ... (la estructura es la misma)
        ]);

        // Verificamos que el monto final en el JSON sea el correcto según el TasaSeeder
        // Como la fecha de transmisión es la fecha fija, no hay recargo por mora.
        // Cálculo: 100,000 (base) * 1.00% (tasa para 'Hijo/a') = 1,000
        $response->assertJsonFragment(['monto_final' => 1000.00]);
        $response->assertJsonFragment(['tributo_omitido' => 1000.00]);
        $response->assertJsonFragment(['tasa' => '1.00']);
        $response->assertJsonFragment(['dias_mora' => 0]);

        // 4. LIMPIEZA: Restablecer el tiempo de Carbon después del test
        Carbon::setTestNow();
    }
}
