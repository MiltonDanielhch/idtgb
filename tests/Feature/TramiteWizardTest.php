<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Person;
use App\Models\Inmueble;
use App\Models\Municipio;
use App\Models\Departamento;
use App\Models\TipoTransmision;
use App\Models\Parentesco;
use App\Models\Tasa;
use App\Models\Tramite;
use App\Models\TipoInmueble;
use Illuminate\Support\Str;
use Database\Seeders\IdtgbMaestrosSeeder;

class TramiteWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Municipio $municipio;
    private TipoTransmision $tipoTransmision;
    private Parentesco $parentesco;
    private TipoInmueble $tipoInmueble;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Cargar TODOS los datos maestros, incluyendo ahora los usuarios.
        $this->seed(IdtgbMaestrosSeeder::class);

        // 2. Obtener los modelos que necesitamos de la base de datos ya poblada.
        $this->municipio = Municipio::first();
        $this->tipoTransmision = TipoTransmision::first();
        $this->parentesco = Parentesco::where('nombre', 'Hijo/a')->firstOrFail();
        $this->tipoInmueble = TipoInmueble::first();

        // 3. Autenticarnos con un usuario conocido y específico del seeder.
        // La contraseña para ese hash suele ser 'password'.
        $this->user = User::where('email', 'admin@admin.com')->firstOrFail();
        $this->actingAs($this->user);
    }

    /**
     * Test completo del wizard de creación de trámites y el cálculo de impuestos.
     *
     * @return void
     */
    public function test_tramite_creation_wizard_calculates_idtgb_correctly()
    {
        // 1. =========== PREPARACIÓN DE DATOS ===========

        // Aseguramos que la tasa para nuestro test sea la que queremos (1%).
        Tasa::where('departamento_id', $this->municipio->provincia->departamento_id)
            ->where('parentesco_id', $this->parentesco->id)
            ->where('tipo_transmision_id', $this->tipoTransmision->id)
            ->delete();

        Tasa::create([
            'departamento_id' => $this->municipio->provincia->departamento_id,
            'parentesco_id' => $this->parentesco->id,
            'tipo_transmision_id' => $this->tipoTransmision->id,
            'tasa' => 1.00, // 1%
            'vigente_desde' => now()->subYear()->toDateString(),
            'vigente_hasta' => null,
        ]);

        // Usamos los datos ya creados por los seeders.
        $disponente = Person::inRandomOrder()->first();
        $adquirente = Person::where('id', '!=', $disponente->id)->inRandomOrder()->first();
        $inmueble = Inmueble::inRandomOrder()->first();

        // Datos para el trámite
        $tramiteData = [
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
            'step1' => $tramiteData,
            'step2' => ['disponentes' => [$disponente->id]],
            // 'step3' => ['adquirentes' => [
            //     ['person_id' => $adquirente->id, 'parentesco_id' => $this->parentesco->id]
            // ]],
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
            'nro_tramite' => $tramiteData['nro_tramite'],
            'base_imponible' => 100000,
        ]);

        $tramite = Tramite::where('nro_tramite', $tramiteData['nro_tramite'])->first();

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
