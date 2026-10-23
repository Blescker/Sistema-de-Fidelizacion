<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_crear_negocio_deja_todo_listo_para_operar(): void
    {
        $super = $this->createSuperAdmin();

        $this->as($super)->postJson('/super/negocios', [
            'name' => 'Pollería El Buen Sabor',
            'industry' => 'restaurante',
            'ruc' => '20555555555',
            'branch_name' => 'Local Surquillo',
            'admin_name' => 'Carlos Ríos',
            'subdomain' => 'buen-sabor',
            'admin_username' => 'carlos.rios',
            'admin_password' => 'Demo1234!',
        ])->assertCreated();

        $business = Business::where('name', 'Pollería El Buen Sabor')->firstOrFail();
        $this->assertSame('PEN', $business->currency);
        $this->assertSame('S/', $business->currency_symbol);
        $this->assertSame('America/Lima', $business->timezone);
        $this->assertSame('S/ 1,250.50', $business->money(1250.5));
        $this->assertTrue(Branch::withoutGlobalScopes()->where('business_id', $business->id)->where('name', 'Local Surquillo')->exists());

        // el admin nuevo puede entrar y administrar su negocio
        auth()->logout();
        $admin = User::withoutGlobalScopes()->where('username', 'carlos.rios')->firstOrFail();
        $this->resetTenant();
        $this->as($admin)->get('/usuarios')->assertOk()->assertSee('Carlos Ríos');
    }

    public function test_suspender_negocio(): void
    {
        $super = $this->createSuperAdmin();
        [$business] = $this->createBusiness();

        $this->as($super)->patchJson("/super/negocios/{$business->id}/estado")->assertOk();
        $this->assertFalse($business->fresh()->isActive());
    }

    public function test_admin_de_negocio_no_puede_crear_negocios(): void
    {
        [, $admin] = $this->createBusiness();

        $this->as($admin)->postJson('/super/negocios', ['name' => 'X'])->assertNotFound();
    }
}
