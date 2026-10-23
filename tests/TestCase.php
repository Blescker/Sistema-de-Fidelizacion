<?php

namespace Tests;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Services\BusinessService;
use App\Support\CurrentBusiness;
use App\Support\DefaultRoles;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /** Crea un negocio completo (roles + sucursal + admin) y devuelve [negocio, admin] */
    protected function createBusiness(string $name = 'Café Test'): array
    {
        $business = app(BusinessService::class)->create([
            'name' => $name,
            'industry' => 'cafeteria',
            'admin_name' => "Admin {$name}",
            'subdomain' => 'neg'.substr(md5(uniqid('', true)), 0, 8),
            'admin_username' => 'admin',
            'admin_password' => 'Demo1234!',
        ]);

        $admin = User::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

        return [$business, $admin];
    }

    protected function createCashier(Business $business, ?Branch $branch = null): User
    {
        $branch ??= Branch::withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

        $cashier = User::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'name' => 'Cajero Test',
            'username' => 'cajero'.uniqid(),
            'password' => 'Demo1234!',
        ]);

        app(BusinessService::class)->withTeam($business, fn () => $cashier->assignRole(DefaultRoles::CASHIER));

        return $cashier;
    }

    protected function createSuperAdmin(): User
    {
        $user = User::create(['name' => 'Super', 'username' => 'super'.uniqid(), 'password' => 'Demo1234!']);
        $user->forceFill(['is_super_admin' => true])->save();

        return $user;
    }

    /**
     * Inicia sesión como $user y hace las peticiones desde SU dirección:
     * el subdominio de su negocio, o el dominio principal si es Super Admin.
     */
    protected function as(User $user): static
    {
        $business = $user->business_id ? Business::find($user->business_id) : null;

        return $this->actingAs($user)->onHost($business);
    }

    /** Host desde el que salen las peticiones del test (null = APP_URL) */
    protected ?string $testHost = null;

    /** Peticiones desde el subdominio de $business (o el dominio principal si es null) */
    protected function onHost(?Business $business): static
    {
        $domain = config('app.domain');
        $this->testHost = $business ? "{$business->slug}.{$domain}" : $domain;

        return $this;
    }

    protected function prepareUrlForRequest($uri)
    {
        if ($this->testHost !== null && str_starts_with($uri, '/')) {
            return "http://{$this->testHost}{$uri}";
        }

        return parent::prepareUrlForRequest($uri);
    }

    /**
     * En la app real cada petición es un proceso nuevo; en los tests todo corre
     * en el mismo proceso, así que limpiamos el "negocio actual" entre peticiones.
     */
    protected function resetTenant(): void
    {
        app(CurrentBusiness::class)->set(null);
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
