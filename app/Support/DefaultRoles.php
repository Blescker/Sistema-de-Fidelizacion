<?php

namespace App\Support;

/**
 * Permisos del sistema y roles que se crean automáticamente en cada negocio.
 * Cuando se agreguen módulos (clientes, compras, cupones...) se suman aquí.
 */
class DefaultRoles
{
    public const ADMIN = 'admin';

    public const CASHIER = 'cajero';

    public const PERMISSIONS = [
        'dashboard.ver',
        'sucursales.gestionar',
        'usuarios.gestionar',
        'configuracion.editar',
    ];

    public const ROLES = [
        self::ADMIN => [
            'dashboard.ver',
            'sucursales.gestionar',
            'usuarios.gestionar',
            'configuracion.editar',
        ],
        self::CASHIER => [
            'dashboard.ver',
        ],
    ];

    public const LABELS = [
        self::ADMIN => 'Administrador',
        self::CASHIER => 'Cajero',
    ];
}
