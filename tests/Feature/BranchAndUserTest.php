<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchAndUserTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Sucursales ----------

    public function test_crear_sucursal_y_queda_auditada(): void
    {
        [$business, $admin] = $this->createBusiness();

        $this->as($admin)
            ->postJson('/sucursales', ['name' => 'Sucursal Surco', 'address' => 'Av. Primavera 100'])
            ->assertCreated()
            ->assertJson(['ok' => true, 'message' => 'Sucursal creada']);

        $branch = Branch::withoutGlobalScopes()->where('name', 'Sucursal Surco')->first();
        $this->assertEquals($business->id, $branch->business_id);
        $this->assertTrue(AuditLog::withoutGlobalScopes()
            ->where('action', 'branch.created')->where('auditable_id', $branch->id)->where('user_id', $admin->id)->exists());
    }

    public function test_sucursal_requiere_nombre(): void
    {
        [, $admin] = $this->createBusiness();

        $this->as($admin)->postJson('/sucursales', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Nombre es obligatorio.');
    }

    public function test_no_se_puede_desactivar_la_ultima_sucursal_activa(): void
    {
        [$business, $admin] = $this->createBusiness();
        $branch = Branch::withoutGlobalScopes()->where('business_id', $business->id)->first();

        $this->as($admin)->patchJson("/sucursales/{$branch->id}/estado")->assertStatus(409);
        $this->assertTrue($branch->fresh()->is_active);
    }

    // ---------- Usuarios ----------

    public function test_crear_cajero_con_sucursal_y_rol(): void
    {
        [$business, $admin] = $this->createBusiness();
        $branch = Branch::withoutGlobalScopes()->where('business_id', $business->id)->first();

        $this->as($admin)->postJson('/usuarios', [
            'name' => 'Luis Mamani',
            'username' => 'Luis.Mamani ',   // se guarda en minúsculas y sin espacios al borde
            'role' => 'cajero',
            'branch_id' => $branch->id,
            'password' => 'Demo1234!',
        ])->assertCreated();

        $user = User::withoutGlobalScopes()->where('username', 'luis.mamani')->first();
        $this->assertEquals($business->id, $user->business_id);
        $this->assertTrue(Hash::check('Demo1234!', $user->password));

        $this->resetTenant();
        $this->as($user)->get('/panel')->assertOk();
        $this->resetTenant();
        $this->as($user)->get('/usuarios')->assertForbidden();
    }

    public function test_usuario_con_espacios_o_repetido_es_rechazado(): void
    {
        [$business, $admin] = $this->createBusiness();
        $cashier = $this->createCashier($business);
        $branch = Branch::withoutGlobalScopes()->where('business_id', $business->id)->first();
        $data = ['name' => 'X', 'role' => 'cajero', 'branch_id' => $branch->id, 'password' => 'Demo1234!'];

        $this->as($admin)->postJson('/usuarios', $data + ['username' => 'ana quispe'])
            ->assertStatus(422)->assertJsonValidationErrors('username');

        $this->resetTenant();
        $this->as($admin)->postJson('/usuarios', $data + ['username' => strtoupper($cashier->username)])
            ->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_cajero_requiere_sucursal(): void
    {
        [, $admin] = $this->createBusiness();

        $this->as($admin)->postJson('/usuarios', [
            'name' => 'Sin sucursal',
            'username' => 'sin.sucursal',
            'role' => 'cajero',
            'password' => 'Demo1234!',
        ])->assertStatus(422)->assertJsonValidationErrors('branch_id');
    }

    public function test_editar_sin_contrasena_no_la_cambia(): void
    {
        [$business, $admin] = $this->createBusiness();
        $cashier = $this->createCashier($business);
        $hash = $cashier->password;

        $this->as($admin)->putJson("/usuarios/{$cashier->id}", [
            'name' => 'Nombre Nuevo',
            'username' => $cashier->username,
            'role' => 'cajero',
            'branch_id' => $cashier->branch_id,
            'password' => '',
        ])->assertOk();

        $fresh = User::withoutGlobalScopes()->find($cashier->id);
        $this->assertEquals('Nombre Nuevo', $fresh->name);
        $this->assertEquals($hash, $fresh->password);
    }

    public function test_admin_no_puede_desactivarse_ni_cambiarse_el_rol(): void
    {
        [, $admin] = $this->createBusiness();

        $this->as($admin)->patchJson("/usuarios/{$admin->id}/estado")->assertStatus(409);

        $this->resetTenant();
        $this->as($admin)->putJson("/usuarios/{$admin->id}", [
            'name' => $admin->name, 'username' => $admin->username, 'role' => 'cajero',
            'branch_id' => Branch::withoutGlobalScopes()->first()->id,
        ])->assertStatus(409);
    }

    public function test_usuario_desactivado_es_sacado_en_su_siguiente_peticion(): void
    {
        [$business, $admin] = $this->createBusiness();
        $cashier = $this->createCashier($business);

        $this->as($admin)->patchJson("/usuarios/{$cashier->id}/estado")->assertOk();

        $this->resetTenant();
        $this->as(User::withoutGlobalScopes()->find($cashier->id))
            ->getJson('/panel')->assertStatus(401);
    }

    // ---------- Configuración ----------

    public function test_configuracion_valida_ruc_de_11_digitos(): void
    {
        [, $admin] = $this->createBusiness();
        $data = ['name' => 'Mi Café', 'industry' => 'cafeteria', 'points_label' => 'Estrellas', 'primary_color' => '#112233'];

        $this->as($admin)->putJson('/configuracion', $data + ['ruc' => '123'])
            ->assertStatus(422)->assertJsonValidationErrors('ruc');

        $this->resetTenant();
        $this->as($admin)->putJson('/configuracion', $data + ['ruc' => '20123456789'])->assertOk();
        $this->assertEquals('Estrellas', $admin->business->fresh()->points_label);
    }
}
