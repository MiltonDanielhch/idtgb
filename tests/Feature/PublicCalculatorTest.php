<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\Tasa;
use App\Models\TipoTransmision;
use Database\Seeders\DepartamentoSeeder;
use Database\Seeders\ParentescoSeeder;
use Database\Seeders\TipoTransmisionSeeder;

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

        // Obtener los modelos que usaremos en las pruebas
        $this->departamento = Departamento::where('codigo', 'BE')->firstOrFail();
        $this->parentescoHijo = Parentesco::where('nombre', 'Hijo/a')->firstOrFail();
        $this->tipoTransmisionDonacion = TipoTransmision::where('nombre', 'Donación')->firstOrFail();

        // Limpiamos y creamos nuestra propia tasa para asegurar un entorno de prueba limpio
        Tasa::query()->delete();
        Tasa::create([
            'departamento_id' => $this->departamento->id,
            'parentesco_id'   => $this->parentescoHijo->id,
            'tipo_transmision_id' => null, // Aplica a todos los tipos de transmisión
            'tasa' => 1.50,
            'vigente_desde' => '2000-01-01',
            'vigente_hasta' => null,
        ]);
    }

    /**
     * @test
     * Simula una llamada AJAX al endpoint de la calculadora y verifica la respuesta JSON.
     */
    public function calculadora_publica_api_calcula_y_devuelve_json_correcto()
    {
        // Arrange: Preparamos los datos del formulario que el script de JS enviaría
        $url = route('calculadora.beni.calcular');
        $formData = [
            'base_imponible'     => 100000,
            'parentesco_id'      => $this->parentescoHijo->id,
            'fecha_transmision'  => now()->toDateString(),
            'tipo_contribuyente' => 'Natural', // Campo requerido por la validación
            'tipo_transmision'   => 'Donación', // Campo requerido por la validación
        ];

        // Act: Hacemos la petición POST, esperando una respuesta JSON
        $response = $this->postJson($url, $formData);

        // Assert: Verificamos la respuesta
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tipo_contribuyente',
            'parentesco',
            'tipo_transmision',
            'fecha_transmision',
            'fecha_vencimiento',
            'dias_mora',
            'base_imponible',
            'ufv',
            'tasa',
            'tributo_omitido',
            'monto_final',
            'cuenta_banco',
        ]);

        // Verificamos que el monto final en el JSON sea el correcto
        $response->assertJsonFragment(['monto_final' => 1500.00]);
        $response->assertJsonFragment(['tributo_omitido' => 1500.00]);
        $response->assertJsonFragment(['tasa' => '1.50']);
    }
}