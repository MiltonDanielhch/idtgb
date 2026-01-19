<?php

namespace Tests\Feature;

use App\Models\Inmueble;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmuebleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('admin.inmuebles.index'));
        $response->assertRedirect();
    }
}
