<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_es_enviado_al_login(): void
    {
        [$business] = $this->createBusiness();

        $this->onHost($business)->get('/panel')->assertRedirect('/login');
        $this->onHost($business)->get('/login')->assertOk()->assertSee('Ingresar')->assertSee($business->name);
    }

    public function test_login_correcto_devuelve_json_con_redireccion(): void
    {
        [$business, $admin] = $this->createBusiness();

        $this->onHost($business)
            ->postJson('/login', ['username' => $admin->username, 'password' => 'Demo1234!'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->last_login_at);
    }

    public function test_login_con_clave_incorrecta(): void
    {
        [$business, $admin] = $this->createBusiness();

        $this->onHost($business)
            ->postJson('/login', ['username' => $admin->username, 'password' => 'mala'])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'message' => 'Usuario o contraseña incorrectos.']);

        $this->assertGuest();
    }

    public function test_validacion_en_espanol(): void
    {
        $this->postJson('/login', [])
            ->assertStatus(422)
            ->assertJsonPath('errors.username.0', 'Usuario es obligatorio.');
    }

    public function test_usuario_desactivado_no_puede_ingresar(): void
    {
        [$business, $admin] = $this->createBusiness();
        $admin->update(['is_active' => false]);

        $this->onHost($business)
            ->postJson('/login', ['username' => $admin->username, 'password' => 'Demo1234!'])
            ->assertStatus(403);
        $this->assertGuest();
    }

    public function test_bloqueo_tras_5_intentos_fallidos(): void
    {
        [$business, $admin] = $this->createBusiness();

        foreach (range(1, 5) as $i) {
            $this->onHost($business)->postJson('/login', ['username' => $admin->username, 'password' => 'mala']);
        }

        $this->onHost($business)
            ->postJson('/login', ['username' => $admin->username, 'password' => 'Demo1234!'])
            ->assertStatus(429);
    }

    public function test_cada_usuario_va_a_su_inicio(): void
    {
        [, $admin] = $this->createBusiness();
        $this->as($admin)->get('/')->assertRedirect(route('dashboard'));

        $super = $this->createSuperAdmin();
        $this->as($super)->get('/')->assertRedirect(route('super.businesses.index'));
    }
}
