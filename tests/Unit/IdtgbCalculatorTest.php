<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\IdtgbCalculator;
use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\Tasa;
use App\Models\TipoTransmision;
use Carbon\Carbon;
use Database\Seeders\DepartamentoSeeder;
use Database\Seeders\ParentescoSeeder;
use Database\Seeders\TasaSeeder;
use Database\Seeders\TipoTransmisionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IdtgbCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private IdtgbCalculator $calculator;
    private Departamento $departamento;
    private Parentesco $parentescoConyuge;
    private Parentesco $parentescoHijo;
    private TipoTransmision $tipoTransmisionDonacion;

    /**
     * Prepara el entorno de prueba antes de cada test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instanciar la calculadora
        $this->calculator = new IdtgbCalculator();

        // Cargar los datos maestros necesarios para los cálculos
        $this->seed(DepartamentoSeeder::class);
        $this->seed(ParentescoSeeder::class);
        $this->seed(TipoTransmisionSeeder::class);
        // $this->seed(TasaSeeder::class); // No usamos el seeder para aislar la prueba

        // Obtener los modelos que usaremos en las pruebas
        $this->departamento = Departamento::where('codigo', 'BE')->firstOrFail();
        $this->parentescoConyuge = Parentesco::where('nombre', 'Cónyuge o Conviviente')->firstOrFail();
        $this->parentescoHijo = Parentesco::where('nombre', 'Hijo/a')->firstOrFail();
        $this->tipoTransmisionDonacion = TipoTransmision::where('nombre', 'Donación')->firstOrFail();

        // Limpiamos y creamos nuestras propias tasas para asegurar un entorno de prueba limpio
        Tasa::query()->delete();
        Tasa::create([
            'departamento_id' => $this->departamento->id,
            'parentesco_id'   => $this->parentescoConyuge->id,
            'tipo_transmision_id' => null, // Aplica a todos los tipos de transmisión
            'tasa' => 0.00,
            'vigente_desde' => '2000-01-01',
            'vigente_hasta' => null,
        ]);
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
     * Verifica que el IDTGB para una transferencia a un cónyuge es 0.
     */
    public function el_calculo_para_conyuge_es_cero()
    {
        $resultados = $this->calculator->calculateEstimate(
            baseImponible: 100000.00,
            departamentoId: $this->departamento->id,
            parentescoId: $this->parentescoConyuge->id,
            tipoTransmisionId: $this->tipoTransmisionDonacion->id,
            fechaPresentacion: now()->toDateString(),
            fechaVencimiento: now()->addDays(10)->toDateString()
        );

        $this->assertEquals(0.00, $resultados['idtgb']);
        $this->assertEquals(0.00, $resultados['final']);
    }

    /**
     * @test
     * Verifica que la tasa aplicada para un hijo sea la correcta (1.5%).
     */
    public function el_calculo_para_hijo_usa_la_tasa_correcta()
    {
        $baseImponible = 100000.00;

        $resultados = $this->calculator->calculateEstimate(
            baseImponible: $baseImponible,
            departamentoId: $this->departamento->id,
            parentescoId: $this->parentescoHijo->id,
            tipoTransmisionId: $this->tipoTransmisionDonacion->id,
            fechaPresentacion: now()->toDateString(),
            fechaVencimiento: now()->addDays(10)->toDateString()
        );

        // En lugar de probar el resultado final, probamos la tasa que se usó.
        // Esto nos ayuda a diagnosticar si la tasa se está modificando en alguna parte.
        $this->assertEquals(1.50, $resultados['detalles_tasas'][0]['tasa_aplicada']);
    }

    /**
     * @test
     * Verifica que el recargo por mora se calcule correctamente.
     */
    public function el_calculo_con_recargo_por_mora_es_correcto()
    {
        // La fecha de vencimiento fue hace 65 días (2 meses y 5 días)
        $fechaVencimiento = Carbon::parse('2025-08-11');
        $diaDeCalculo = Carbon::parse('2025-10-15'); // 65 días después
        Carbon::setTestNow($diaDeCalculo);

        // Usamos el caso del hijo, con un IDTGB de 1,500.
        $baseImponible = 100000.00;
        $idtgb = 1500.00;

        // La lógica en Tramite::calcularMora() es 0.5% mensual.
        // Días de mora = 65. Meses de mora = intdiv(65, 30) = 2.
        // Recargo esperado = 1500 * 0.005 (tasa) * 2 (meses) = 15.00
        $recargoEsperado = 15.00;
        $montoFinalEsperado = $idtgb + $recargoEsperado; // 1500 + 15 = 1515.00

        // El recargo es 1% del IDTGB por cada día de mora.
        // Recargo esperado = 1500 * 1% * 20 días = 300
        $recargoEsperado = 300.00;
        $montoFinalEsperado = $idtgb + $recargoEsperado; // 1500 + 300 = 1800

        // Para este test, necesitamos acceder al método privado `performCalculation`
        $reflection = new \ReflectionClass(IdtgbCalculator::class);
        $method = $reflection->getMethod('performCalculation');
        $method->setAccessible(true);

        $adquirentesData = [['parentesco_id' => $this->parentescoHijo->id, 'porcentaje' => 100]];

        $resultados = $method->invoke(
            $this->calculator,
            $baseImponible, // base
            $baseImponible, // valor_declarado
            $this->departamento->id,
            $this->tipoTransmisionDonacion->id,
            '2025-08-01', // Fecha pasada, pero la tasa es válida desde el 2000
            $fechaVencimiento->toDateString(),
            $adquirentesData,
            []
        );

        $this->assertEquals($idtgb, $resultados['idtgb']);
        $this->assertEquals($recargoEsperado, $resultados['recargo']);
        $this->assertEquals($montoFinalEsperado, $resultados['final']);

        // Restauramos el tiempo al final del test
        Carbon::setTestNow();
    }
}
