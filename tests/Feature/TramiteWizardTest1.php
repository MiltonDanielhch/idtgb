<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{
    User, Person, Inmueble, Municipio, TipoTransmision, Parentesco, TipoInmueble, Tramite, Tasa
};
// use App\Models\Tasa;
// use App\Models\Tramite;
// use App\Models\TipoInmueble;
use Illuminate\Support\Str;
use Database\Seeders\IdtgbMaestrosSeeder;

class TramiteWizardTest1 extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TipoTransmision $tipoTransmision;
    private Parentesco $parentesco;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Cargar TODOS los datos maestros, incluyendo ahora los usuarios.
        $this->seed(IdtgbMaestrosSeeder::class);

        // 2. Obtener los modelos que necesitamos de la base de datos ya poblada.
        $this->tipoTransmision = TipoTransmision::first();
        $this->parentesco = Parentesco::where('nombre', 'Hijo/a')->firstOrFail();

        // 3. Autenticarnos con un usuario conocido y específico del seeder.
        // La contraseña para ese hash suele ser 'password'.
        $this->user = User::where('email', 'admin@admin.com')->firstOrFail();
        $this->actingAs($this->user);
    }

    /**
     * Prepara los datos de prueba para el wizard.
     * @return array
     */
    private function prepareTestData(): array
    {
        // Aseguramos que la tasa para nuestro test sea la que queremos (1%).
        $municipio = Municipio::first();
        Tasa::updateOrCreate(
            [
                'departamento_id' => $municipio->provincia->departamento_id,
                'parentesco_id' => $this->parentesco->id,
                'tipo_transmision_id' => $this->tipoTransmision->id,
            ],
            [
                'tasa' => 1.00, // 1%
                'vigente_desde' => now()->subYear()->toDateString(),
                'vigente_hasta' => null,
            ]
        );

        $disponente = Person::inRandomOrder()->first();
        $adquirente = Person::where('id', '!=', $disponente->id)->inRandomOrder()->first();
        $inmueble = Inmueble::inRandomOrder()->first();

        return [$disponente, $adquirente, $inmueble];
    }

    /**
     * Test completo del wizard de creación de trámites y el cálculo de impuestos.
     *
     * @return void
     */
    public function test_tramite_creation_wizard_calculates_idtgb_correctly()
    {
        // 1. =========== PREPARACIÓN DE DATOS ===========
        [$disponente, $adquirente, $inmueble] = $this->prepareTestData();

        // Datos para el trámite
        $step1Data = [
            'nro_tramite' => 'TEST-' . Str::random(5),
            'fecha_presentacion' => now()->toDateString(),
            'fecha_transmision' => now()->subDays(10)->toDateString(),
            'tipo_transmision_id' => $this->tipoTransmision->id,
            'valor_declarado' => 100000,
            'base_imponible' => 100000,
            'observaciones' => 'Trámite de prueba automatizada',
        ];

        // 2. =========== SIMULACIÓN DEL WIZARD ===========
        $sessionData = [
            'step1' => $step1Data,
            'step2' => ['disponentes' => [$disponente->id]],
            'step3' => ['adquirentes' => [
                [
                    'person_id' => $adquirente->id,
                    'parentesco_id' => $this->parentesco->id,
                    'porcentaje' => 100.00 // <-- ¡AÑADIR EL PORCENTAJE QUE FALTABA!
                ]
            ]],
            'step4' => ['inmuebles' => [$inmueble->id]],
        ];

        $this->withSession(['tramite_wizard_data' => $sessionData])
             ->post(route('admin.tramites.wizard.store'));

        // 3. =========== ASEVERACIONES (ASSERTIONS) ===========
        $this->assertDatabaseHas('tramites', [
            'nro_tramite' => $step1Data['nro_tramite'],
            'base_imponible' => 100000,
        ]);

        $tramite = Tramite::where('nro_tramite', $step1Data['nro_tramite'])->firstOrFail();

        $this->assertEquals(1000.00, $tramite->total_idtgb, 'El Total IDTGB no se calculó correctamente.');
        $this->assertEquals(0, $tramite->recargo_mora, 'El recargo por mora debería ser 0.');
        $this->assertEquals(1000.00, $tramite->monto_final, 'El Monto Final no se calculó correctamente.');

        $this->assertCount(1, $tramite->disponentes, 'El número de disponentes no es correcto.');
        $this->assertCount(1, $tramite->adquirentes, 'El número de adquirentes no es correcto.');
        $this->assertCount(1, $tramite->inmuebles, 'El número de inmuebles no es correcto.');

        $adquirenteTramite = $tramite->adquirentes->first();
        $this->assertEquals(1.00, $adquirenteTramite->tasa_aplicada, 'La tasa aplicada al adquirente no es correcta.');
    }
}
