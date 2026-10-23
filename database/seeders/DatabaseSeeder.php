<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Services\BusinessService;
use App\Support\DefaultRoles;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba (NO usar estas contraseñas en producción):
 *   Super Admin  superadmin / Demo1234!   (en localhost, dominio principal)
 *   Admin        rosa.admin / Demo1234!   (en cafe-miraflores.localhost)
 *   Cajero       ana.cajero / Demo1234!   (en cafe-miraflores.localhost)
 */
class DatabaseSeeder extends Seeder
{
    public function run(BusinessService $service): void
    {
        $super = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'password' => 'Demo1234!',
        ]);
        $super->forceFill(['is_super_admin' => true])->save();

        $business = $service->create([
            'name' => 'Café Miraflores',
            'subdomain' => 'cafe-miraflores',
            'industry' => 'cafeteria',
            'ruc' => '20123456789',
            'branch_name' => 'Sucursal Larco',
            'admin_name' => 'Rosa Huamán',
            'admin_username' => 'rosa.admin',
            'admin_password' => 'Demo1234!',
        ]);
        $business->update(['points_label' => 'Puntos']);

        Branch::where('business_id', $business->id)->first()
            ->update(['address' => 'Av. José Larco 345, Miraflores']);

        $second = Branch::create([
            'business_id' => $business->id,
            'name' => 'Sucursal San Isidro',
            'address' => 'Av. Javier Prado Oeste 1120, San Isidro',
        ]);

        $cashier = User::create([
            'business_id' => $business->id,
            'branch_id' => $second->id,
            'name' => 'Ana Quispe',
            'username' => 'ana.cajero',
            'phone' => '987654321',
            'password' => 'Demo1234!',
        ]);
        $service->withTeam($business, fn () => $cashier->assignRole(DefaultRoles::CASHIER));
    }
}
