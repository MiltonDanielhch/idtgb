<?php

namespace Tests\Unit;

use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Models\Ufv;
use App\Services\IdtgbCalculator;
use Database\Seeders\IdtgbMaestrosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdtgbCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private IdtgbCalculator $calculator;
    private Departamento $departamento;
    private TipoTransmision $tipoTransmisionDonacion;
    private Parentesco $parentescoHijo;
    private Parentesco $parentescoTioSobrino;


    /**
     * Prepara el entorno de prueba cargando los datos maestros.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdtgbMaestrosSeeder::class);

        $this->calculator = new IdtgbCalculator();
        $this->departamento = Departamento::where('codigo', Departamento::CODIGO_BENI)->firstOrFail();
        $this->tipoTransmisionDonacion = TipoTransmision::where('nombre', 'Donación')->firstOrFail();
        $this->parentescoHijo = Parentesco::where('nombre', 'Hijo/a')->firstOrFail();
        $this->parentescoTioSobrino = Parentesco::where('nombre', 'Tío/a o Sobrino/a')->firstOrFail();
    }

    /**
     * @test
     * Verifica que el cálculo proporcional del IDTGB sea correcto para un adquirente.
     */
    public function it_calculates_idtgb_for_a_single_adquirente_without_mora()
    {
        // 1. Arrange (Preparar los datos para este escenario específico)
        $baseImponible = 200000; // Bs. 200,000
        $fechaTransmision = now()->subDays(10);
        $fechaPresentacion = now(); // Se presenta dentro del plazo
        $fechaVencimiento = $fechaTransmision->copy()->addDays(30);

        // 2. Act (Actuar)
        // Usamos el método público `calculateEstimate` que está diseñado para este propósito.
        $resultados = $this->calculator->calculateEstimate(
            $baseImponible,
            $this->departamento->id,
            $this->parentescoHijo->id,
            $this->tipoTransmisionDonacion->id,
            $fechaTransmision->toDateString(),
            $fechaPresentacion->toDateString(),
            $fechaVencimiento->toDateString()
        );

        // 3. Assert (Verificar)
        // Lógica del cálculo:
        // Tasa para 'Hijo/a' es 1% según los seeders.
        // IDTGB = 200,000 * 1% = 2,000
        $this->assertEquals(2000.00, $resultados['idtgb']);
        $this->assertEquals(0, $resultados['recargo'], 'No debería haber recargo por mora.');
        $this->assertEquals(2000.00, $resultados['final']);
        $this->assertEquals(1.00, $resultados['detalles_tasas'][0]['tasa_aplicada']);
        $this->assertEquals(2000.00, $resultados['detalles_tasas'][0]['proporcional']);
    }

    /**
     * @test
     * Verifica el cálculo con múltiples adquirentes y recargo por mora.
     */
    public function it_calculates_idtgb_for_multiple_adquirentes_with_mora()
    {
        // 1. Arrange
        $baseImponible = 500000; // Bs. 500,000
        $fechaTransmision = now()->subDays(50); // Hace 50 días
        $fechaPresentacion = now(); // Se presenta hoy, con mora
        $fechaVencimiento = $fechaTransmision->copy()->addDays(30); // Venció hace 20 días

        // Para probar múltiples adquirentes, necesitamos usar Reflection para acceder al método privado,
        // ya que los métodos públicos no soportan este caso de forma directa para un test unitario puro.
        // Esto es una excepción aceptada cuando se necesita probar lógica interna compleja.
        $reflection = new \ReflectionClass(IdtgbCalculator::class);
        $method = $reflection->getMethod('performCalculation');
        $method->setAccessible(true);

        // 2. Act
        $resultados = $method->invokeArgs($this->calculator, [
             $baseImponible,
             $this->departamento->id,
             $this->tipoTransmisionDonacion->id,
             $fechaPresentacion->toDateString(),
             $fechaTransmision->toDateString(),
             $fechaVencimiento->toDateString(),
             [ // Adquirentes Data
                 [ 'parentesco_id' => $this->parentescoHijo->id, 'porcentaje' => 60.00 ],
                 [ 'parentesco_id' => $this->parentescoTioSobrino->id, 'porcentaje' => 40.00 ]
             ],
             [] // Sin exenciones
        ]);

        // 3. Assert
        // Lógica del cálculo:
        // Adquirente 1 (Hijo, 1%): 500,000 * 60% * 1% = 3,000
        // Adquirente 2 (Tío/Sobrino, 20%): 500,000 * 40% * 20% = 40,000
        // IDTGB Total = 3,000 + 40,000 = 43,000
        // Días de mora = now() vs (fecha_transmision + 30 días) -> 20 días
        // Recargo = 43,000 * 1% * 20 días = 8,600
        // Monto Final = 43,000 + 8,600 = 51,600

        $this->assertEquals(3000.00, $resultados['detalles_tasas'][0]['proporcional'], 'IDTGB proporcional del hijo es incorrecto.');
        $this->assertEquals(40000.00, $resultados['detalles_tasas'][1]['proporcional'], 'IDTGB proporcional del tío/sobrino es incorrecto.');
        $this->assertEquals(43000.00, $resultados['idtgb'], 'El IDTGB total es incorrecto.');
        $this->assertEquals(20, $resultados['dias_mora'], 'Los días de mora son incorrectos.');
        $this->assertEquals(8600.00, $resultados['recargo'], 'El recargo por mora es incorrecto.');
        $this->assertEquals(51600.00, $resultados['final'], 'El monto final es incorrecto.');
    }
}
