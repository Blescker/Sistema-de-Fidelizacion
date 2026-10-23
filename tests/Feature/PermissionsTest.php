<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cajero_solo_ve_el_inicio(): void
    {
        [$business] = $this->createBusiness();
        $cashier = $this->createCashier($business);

        foreach (['/panel' => 200, '/sucursales' => 403, '/usuarios' => 403, '/configuracion' => 403, '/super/negocios' => 404] as $url => $status) {
            $this->resetTenant();
            $this->as($cashier)->get($url)->assertStatus($status);
        }
    }

    public function test_admin_ve_todo_su_negocio_pero_no_el_panel_super(): void
    {
        [, $admin] = $this->createBusiness();

        foreach (['/panel' => 200, '/sucursales' => 200, '/usuarios' => 200, '/configuracion' => 200, '/super/negocios' => 404] as $url => $status) {
            $this->resetTenant();
            $this->as($admin)->get($url)->assertStatus($status);
        }
    }

    public function test_super_admin_no_entra_a_pantallas_de_negocio(): void
    {
        $super = $this->createSuperAdmin();

        $this->as($super)->get('/super/negocios')->assertOk();
        $this->as($super)->get('/panel')->assertNotFound();
    }

    public function test_menu_del_cajero_no_muestra_opciones_de_admin(): void
    {
        [$business] = $this->createBusiness();
        $cashier = $this->createCashier($business);

        $this->as($cashier)->get('/panel')
            ->assertOk()
            ->assertDontSee(route('users.index'))
            ->assertDontSee(route('branches.index'));
    }
}
