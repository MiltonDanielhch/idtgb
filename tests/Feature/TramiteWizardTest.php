<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class TramiteWizardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * Prepara el entorno de prueba antes de cada test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Creamos un usuario para usarlo como funcionario autenticado
        $this->user = User::factory()->create();
    }

    /**
     * @test
     * Verifica que un visitante no autenticado sea redirigido al login.
     */
    public function un_visitante_no_puede_ver_el_asistente_de_tramites()
    {
        // Arrange: La URL del primer paso del asistente
        $url = route('admin.tramites.wizard.create.step1');

        // Act: Intentamos acceder a la URL sin iniciar sesión
        $response = $this->get($url);

        // Assert: Verificamos que se redirige a la página de login
        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * Verifica que un funcionario autenticado puede ver el primer paso del asistente.
     */
    public function un_funcionario_autenticado_puede_ver_el_primer_paso_del_asistente()
    {
        // Arrange: La URL del primer paso del asistente
        $url = route('admin.tramites.wizard.create.step1');

        // Act: Iniciamos sesión como el usuario y accedemos a la URL
        $response = $this->actingAs($this->user)->get($url);

        // Assert: Verificamos que la respuesta es exitosa y vemos un texto clave del paso 1
        $response->assertStatus(200);
        $response->assertSee('Paso 1'); // Asumimos que la vista contiene el texto "Paso 1"
    }

    /**
     * @test
     * Simula la creación de un trámite completo a través del asistente.
     */
    public function un_funcionario_puede_crear_un_tramite_completo_a_traves_del_asistente()
    {
        // 1. Arrange: Crear datos necesarios
        $tipoTransmision = \App\Models\TipoTransmision::factory()->create();
        $disponente = \App\Models\Person::factory()->create(['registerUser_id' => $this->user->id]);
        $adquirente = \App\Models\Person::factory()->create(['registerUser_id' => $this->user->id]);
        $parentesco = \App\Models\Parentesco::factory()->create();
        $inmueble = \App\Models\Inmueble::factory()->create();

        // 2. Act & Assert
        // Paso 1: Datos Generales
        $response = $this->actingAs($this->user)
            ->post(route('admin.tramites.wizard.post.step1'), [
                'nro_tramite' => 'TEST-001',
                'fecha_presentacion' => now()->toDateString(),
                'fecha_transmision' => now()->subDay()->toDateString(),
                'tipo_transmision_id' => $tipoTransmision->id,
                'valor_declarado' => 100000,
                'base_imponible' => 80000,
            ]);
        $response->assertRedirect(route('admin.tramites.wizard.create.step2'));

        // Paso 2: Disponentes
        $response = $this->actingAs($this->user)
            ->post(route('admin.tramites.wizard.add.disponente'), ['person_id' => $disponente->id]);
        $response->assertRedirect(route('admin.tramites.wizard.create.step2'));

        $response = $this->actingAs($this->user)
            ->post(route('admin.tramites.wizard.post.step2'));
        $response->assertRedirect(route('admin.tramites.wizard.create.step3'));

        // Paso 3: Adquirentes
        $response = $this->actingAs($this->user)
            ->post(route('admin.tramites.wizard.add.adquirente'), [
                'person_id' => $adquirente->id,
                'parentesco_id' => $parentesco->id,
            ]);
        $response->assertRedirect(route('admin.tramites.wizard.create.step3'));

        $response = $this->actingAs($this->user)
            ->post(route('admin.tramites.wizard.post.step3'));
        $response->assertRedirect(route('admin.tramites.wizard.create.step4'));

        // Paso 4: Inmueble
        $response = $this->actingAs($this->user)
            ->post(route('admin.tramites.wizard.add.inmueble'), ['inmueble_id' => $inmueble->id]);
        $response->assertRedirect(route('admin.tramites.wizard.create.step4'));

        $response = $this->actingAs($this->user)
            ->post(route('admin.tramites.wizard.post.step4'));
        $response->assertRedirect(route('admin.tramites.wizard.create.step5'));

        // Paso 5: Guardar
        $response = $this->actingAs($this->user)
            ->post(route('admin.tramites.wizard.store'));

        // 3. Assert: Verificar que todo se guardó correctamente
        $response->assertRedirect(route('admin.tramites.index'));
        $this->assertDatabaseHas('tramites', [
            'nro_tramite' => 'TEST-001',
            'valor_declarado' => 100000,
        ]);
        $this->assertDatabaseHas('disponentes_tramite', [
            'person_id' => $disponente->id,
        ]);
        $this->assertDatabaseHas('adquirentes_tramite', [
            'person_id' => $adquirente->id,
        ]);
        $this->assertDatabaseHas('tramite_inmuebles', [
            'inmueble_id' => $inmueble->id,
        ]);
    }
}
