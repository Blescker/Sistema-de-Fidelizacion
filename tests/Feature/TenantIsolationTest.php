<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Un negocio nunca puede ver ni tocar datos de otro */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_ve_sucursales_ni_usuarios_de_otro_negocio(): void
    {
        [, $adminA] = $this->createBusiness('Negocio A');
        [$businessB, $adminB] = $this->createBusiness('Negocio B');
        Branch::create(['business_id' => $businessB->id, 'name' => 'Secreta de B']);

        $this->as($adminA)->get('/sucursales')->assertOk()->assertDontSee('Secreta de B');
        $this->resetTenant();
        $this->as($adminA)->get('/usuarios')->assertOk()->assertDontSee($adminB->name);
    }

    public function test_no_puede_editar_sucursal_de_otro_negocio(): void
    {
        [, $adminA] = $this->createBusiness('Negocio A');
        [$businessB] = $this->createBusiness('Negocio B');
        $branchB = Branch::withoutGlobalScopes()->where('business_id', $businessB->id)->first();

        $this->as($adminA)
            ->putJson("/sucursales/{$branchB->id}", ['name' => 'Hackeada'])
            ->assertNotFound();

        $this->resetTenant();
        $this->as($adminA)->patchJson("/sucursales/{$branchB->id}/estado")->assertNotFound();

        $this->assertNotEquals('Hackeada', $branchB->fresh()->name);
        $this->assertTrue($branchB->fresh()->is_active);
    }

    public function test_no_puede_desactivar_usuario_de_otro_negocio(): void
    {
        [, $adminA] = $this->createBusiness('Negocio A');
        [, $adminB] = $this->createBusiness('Negocio B');

        $this->as($adminA)->patchJson("/usuarios/{$adminB->id}/estado")->assertNotFound();
        $this->assertTrue(User::withoutGlobalScopes()->find($adminB->id)->is_active);
    }

    public function test_no_puede_asignar_sucursal_de_otro_negocio(): void
    {
        [, $adminA] = $this->createBusiness('Negocio A');
        [$businessB] = $this->createBusiness('Negocio B');
        $branchB = Branch::withoutGlobalScopes()->where('business_id', $businessB->id)->first();

        $this->as($adminA)->postJson('/usuarios', [
            'name' => 'Intruso',
            'username' => 'intruso',
            'role' => 'cajero',
            'branch_id' => $branchB->id,
            'password' => 'Demo1234!',
        ])->assertStatus(422)->assertJsonValidationErrors('branch_id');
    }

    public function test_mismo_nombre_de_sucursal_permitido_en_otro_negocio(): void
    {
        [, $adminA] = $this->createBusiness('Negocio A');
        [, $adminB] = $this->createBusiness('Negocio B');

        $this->as($adminA)->postJson('/sucursales', ['name' => 'Centro'])->assertCreated();
        $this->resetTenant();
        $this->as($adminB)->postJson('/sucursales', ['name' => 'Centro'])->assertCreated();
        $this->resetTenant();
        $this->as($adminB)->postJson('/sucursales', ['name' => 'Centro'])
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }
}
