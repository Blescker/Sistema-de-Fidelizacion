<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Support\DefaultRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BusinessService
{
    /**
     * Da de alta un negocio completo: negocio + roles por defecto
     * + sucursal principal + usuario administrador.
     *
     * @param  array{name:string, subdomain?:string, industry?:string, ruc?:?string, branch_name?:string,
     *               admin_name:string, admin_username:string, admin_password:string}  $data
     */
    public function create(array $data): Business
    {
        return DB::transaction(function () use ($data) {
            $business = Business::create([
                'name' => $data['name'],
                'slug' => $data['subdomain'] ?? $this->uniqueSlug($data['name']),
                'ruc' => $data['ruc'] ?? null,
                'industry' => $data['industry'] ?? 'otro',
            ]);

            $this->createDefaultRoles($business);

            $branch = Branch::create([
                'business_id' => $business->id,
                'name' => $data['branch_name'] ?? 'Principal',
            ]);

            $admin = User::create([
                'business_id' => $business->id,
                'name' => $data['admin_name'],
                'username' => $data['admin_username'],
                'password' => $data['admin_password'],
            ]);

            $this->withTeam($business, fn () => $admin->assignRole(DefaultRoles::ADMIN));

            AuditLog::record('business.created', $business, null, $business->only('name', 'industry', 'ruc'));

            return $business;
        });
    }

    public function createDefaultRoles(Business $business): void
    {
        foreach (DefaultRoles::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->withTeam($business, function () use ($business) {
            foreach (DefaultRoles::ROLES as $roleName => $permissions) {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'business_id' => $business->id,
                ]);
                $role->syncPermissions($permissions);
            }
        });
    }

    /**
     * Ejecuta algo con el "team" de spatie apuntando a este negocio
     * y luego deja el team como estaba.
     */
    public function withTeam(Business $business, callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($business->id);

        try {
            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'negocio';
        $slug = $base;
        $i = 2;

        while (Business::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
