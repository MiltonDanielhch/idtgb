<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\IdtgbCalculator;
use App\Models\Tramite;
use App\Models\AdquirenteTramite;
use App\Models\Inmueble;
use App\Models\Municipio;
use App\Models\Provincia;
use App\Models\Departamento;
use App\Models\Tasa;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

class IdtgbCalculatorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Verifica que el cálculo proporcional del IDTGB sea correcto para un adquirente.
     */
    public function it_calculates_proportional_idtgb_correctly()
    {
        // 1. Arrange (Preparar)
        // Creamos los datos maestros necesarios en memoria o en la BD de prueba.
        $departamento = Departamento::factory()->create();
        $parentesco = Parentesco::factory()->create();
        $tipoTransmision = TipoTransmision::factory()->create();
        Tasa::factory()->create([
            'departamento_id' => $departamento->id,
            'parentesco_id' => $parentesco->id,
            'tipo_transmision_id' => $tipoTransmision->id,
            'tasa' => 10.00, // 10%
            'vigente_desde' => now()->subYear(),
            'vigente_hasta' => null,
        ]);

        // Creamos un objeto "mock" (simulado) de Tramite.
        // No necesitamos que exista en la base de datos para este test.
        $tramite = new Tramite([
            'base_imponible' => 200000, // Bs. 200,000
            'fecha_presentacion' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'tipo_transmision_id' => $tipoTransmision->id,
        ]);

        // Creamos un adquirente con una tasa y porcentaje específicos.
        $adquirente = new AdquirenteTramite([
            'parentesco_id' => $parentesco->id,
            'porcentaje' => 50.00,    // Adquiere el 50% del bien
        ]);

        // Creamos una colección de adquirentes y la asignamos al trámite simulado.
        $tramite->setRelation('adquirentes', new Collection([$adquirente]));

        // Instanciamos nuestro servicio de cálculo.
        $calculator = new IdtgbCalculator();

        // 2. Act (Actuar)
        // Ejecutamos el método que queremos probar.
        $resultados = $calculator->performCalculation(
            $tramite->base_imponible,
            $departamento->id,
            $tramite->tipo_transmision_id,
            $tramite->fecha_presentacion,
            $tramite->fecha_vencimiento,
            $tramite->adquirentes->toArray(),
            [] // Sin exenciones
        );

        // 3. Assert (Verificar)
        // Verificamos que el resultado sea el esperado.
        // Lógica del cálculo:
        // Impuesto Total = 200,000 (base) * 10% (tasa) = 20,000
        // IDTGB Proporcional = 20,000 (impuesto total) * 50% (porcentaje del adquirente) = 10,000
        $this->assertEquals(10000.00, $resultados['detalles_tasas'][0]['proporcional']);
        $this->assertEquals(10000.00, $resultados['idtgb']);
    }
}
