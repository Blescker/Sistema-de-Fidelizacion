<?php

namespace Tests\Feature;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Cada negocio vive en su subdominio; el dominio principal es de la plataforma */
class SubdomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_subdominio_inexistente_da_404(): void
    {
        $this->get('http://noexiste.localhost/login')->assertNotFound();
    }

    public function test_negocio_suspendido_muestra_aviso(): void
    {
        [$business] = $this->createBusiness('Café Cerrado');
        $business->update(['status' => Business::STATUS_SUSPENDED]);

        $this->onHost($business)->get('/login')
            ->assertForbidden()
            ->assertSee('suspendido');
    }

    public function test_admin_de_un_negocio_no_entra_por_el_subdominio_de_otro(): void
    {
        [, $adminA] = $this->createBusiness('Negocio A');
        [$businessB] = $this->createBusiness('Negocio B');

        // ambos se llaman "admin", pero la clave de A no sirve en B... porque A no existe en B
        $adminA->update(['password' => 'ClaveDeA123']);

        $this->onHost($businessB)
            ->postJson('/login', ['username' => 'admin', 'password' => 'ClaveDeA123'])
            ->assertStatus(422);
        $this->assertGuest();
    }

    public function test_mismo_usuario_en_dos_negocios_entra_cada_uno_al_suyo(): void
    {
        [$businessA, $adminA] = $this->createBusiness('Negocio A');
        [$businessB, $adminB] = $this->createBusiness('Negocio B');
        $this->assertSame($adminA->username, $adminB->username);

        $this->onHost($businessB)->postJson('/login', ['username' => 'admin', 'password' => 'Demo1234!'])->assertOk();
        $this->assertAuthenticatedAs($adminB);
    }

    public function test_sesion_de_un_negocio_no_sirve_en_el_subdominio_de_otro(): void
    {
        [, $adminA] = $this->createBusiness('Negocio A');
        [$businessB] = $this->createBusiness('Negocio B');

        $this->actingAs($adminA)->onHost($businessB)->get('/panel')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_super_admin_solo_entra_por_el_dominio_principal(): void
    {
        [$business, $admin] = $this->createBusiness();
        $super = $this->createSuperAdmin();

        $this->onHost($business)
            ->postJson('/login', ['username' => $super->username, 'password' => 'Demo1234!'])
            ->assertStatus(422);

        // y el admin de un negocio no entra por el dominio principal
        $this->onHost(null)
            ->postJson('/login', ['username' => $admin->username, 'password' => 'Demo1234!'])
            ->assertStatus(422);

        $this->onHost(null)
            ->postJson('/login', ['username' => $super->username, 'password' => 'Demo1234!'])
            ->assertOk();
    }

    public function test_pantallas_de_negocio_no_existen_en_el_dominio_principal_y_viceversa(): void
    {
        [, $admin] = $this->createBusiness();
        $super = $this->createSuperAdmin();

        $this->as($super)->get('/panel')->assertNotFound();
        $this->as($admin)->get('/super/negocios')->assertNotFound();
    }

    public function test_cada_negocio_es_una_app_con_su_nombre_y_color(): void
    {
        [$business] = $this->createBusiness('Pollería Rica');
        $business->update(['primary_color' => '#DC2626']);

        $this->onHost($business)->get('/manifest.webmanifest')
            ->assertOk()
            ->assertJson(['name' => 'Pollería Rica', 'theme_color' => '#DC2626', 'start_url' => '/']);
    }

    public function test_subdominio_reservado_o_repetido_es_rechazado(): void
    {
        [$existing] = $this->createBusiness();
        $super = $this->createSuperAdmin();
        $data = [
            'name' => 'Nuevo', 'industry' => 'otro',
            'admin_name' => 'Jefe', 'admin_username' => 'jefe', 'admin_password' => 'Demo1234!',
        ];

        foreach (['www', $existing->slug, 'con espacio', '-malo', 'MAYUS_y_guion_bajo'] as $sub) {
            $this->as($super)->postJson('/super/negocios', $data + ['subdomain' => $sub])
                ->assertStatus(422)->assertJsonValidationErrors('subdomain');
        }
    }
}
