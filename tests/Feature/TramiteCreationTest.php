<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Person;
use App\Models\TipoTransmision;
use App\Models\TipoInmueble;
use App\Models\Parentesco;
use App\Models\Municipio;
use App\Models\Inmueble;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use TCG\Voyager\Models\Role;

class TramiteCreationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_admin_can_create_a_tramite()
    {
        // 1. Preparación de datos

        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $user = User::factory()->create(['role_id' => $adminRole->id]);

        $this->actingAs($user);

        $disponente = Person::factory()->create(['registerUser_id' => $user->id]);
        $adquirente = Person::factory()->create(['registerUser_id' => $user->id]);
        $tipoTransmision = TipoTransmision::factory()->create();
        $municipio = Municipio::factory()->create();
        $tipoInmueble = TipoInmueble::factory()->create();
        $parentesco = Parentesco::factory()->create();

        // 2. Simulación de la creación del trámite
        $response = $this->actingAs($user)
            ->get(route('admin.tramites.wizard.create.step1'));

        $response->assertStatus(200);

        // Step 1: Tipo de transmisión
        $response = $this->post(route('admin.tramites.wizard.post.step1'), [
            'tipo_transmision_id' => $tipoTransmision->id,
            'nro_tramite' => 'TEST-' . uniqid(),
            'fecha_presentacion' => now()->toDateString(),
            'fecha_transmision' => now()->subDay()->toDateString(),
            'valor_declarado' => 100000,
            'base_imponible' => 80000,
            'observaciones' => 'Test observation',
        ]);
        $response->assertRedirect(route('admin.tramites.wizard.create.step2'));

        // Step 2: Disponentes
        $response = $this->get(route('admin.tramites.wizard.create.step2'));
        $response->assertStatus(200);

        $response = $this->post(route('admin.tramites.wizard.add.disponente'), [
            'person_id' => $disponente->id,
        ]);
        $response->assertRedirect(route('admin.tramites.wizard.create.step2'));

        $response = $this->post(route('admin.tramites.wizard.post.step2'));
        $response->assertRedirect(route('admin.tramites.wizard.create.step3'));

        // Step 3: Adquirentes
        $response = $this->get(route('admin.tramites.wizard.create.step3'));
        $response->assertStatus(200);

        $response = $this->post(route('admin.tramites.wizard.add.adquirente'), [
            'person_id' => $adquirente->id,
            'parentesco_id' => $parentesco->id,
            'porcentaje' => 100,
        ]);
        $response->assertRedirect(route('admin.tramites.wizard.create.step3'));

        $response = $this->post(route('admin.tramites.wizard.post.step3'));
        $response->assertRedirect(route('admin.tramites.wizard.create.step4'));

        // Step 4: Inmueble
        $response = $this->get(route('admin.tramites.wizard.create.step4'));
        $response->assertStatus(200);
        $parentesco = Parentesco::factory()->create();
        $inmuebleData = [
            'municipio_id' => $municipio->id,
            'tipo_inmueble_id' => $tipoInmueble->id,
            'zona' => 'Central',
            'direccion' => 'Calle Falsa 123',
            'superficie_terreno' => 200,
            'superficie_construccion' => 150,
            'valor_declarado' => 100000,
        ];

        $response = $this->post(route('admin.tramites.wizard.add.inmueble'), $inmuebleData);
        $response->assertRedirect(route('admin.tramites.wizard.create.step4'));

        $response = $this->post(route('admin.tramites.wizard.post.step4'));
        $response->assertRedirect(route('admin.tramites.wizard.create.step5'));

        // Step 5: Resumen y Guardar
        $response = $this->get(route('admin.tramites.wizard.create.step5'));
        $response->assertStatus(200);

        $response = $this->post(route('admin.tramites.wizard.store'));

        // 3. Verificación
        $this->assertDatabaseHas('tramites', [
            'tipo_transmision_id' => $tipoTransmision->id,
            'estado' => 'Borrador',
        ]);

        $response->assertRedirect(route('admin.tramites.index'));
    }
}