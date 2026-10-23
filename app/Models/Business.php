<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const INDUSTRIES = [
        'cafeteria' => 'Cafetería',
        'restaurante' => 'Restaurante',
        'retail' => 'Retail / tienda',
        'gimnasio' => 'Gimnasio',
        'peluqueria' => 'Peluquería / estética',
        'lavado' => 'Lavado de autos / taller',
        'farmacia' => 'Farmacia / minimarket',
        'otro' => 'Otro',
    ];

    /** Subdominios que no puede usar un negocio */
    public const RESERVED_SUBDOMAINS = [
        'www', 'app', 'admin', 'super', 'api', 'panel', 'mail', 'webmail', 'smtp',
        'ftp', 'cpanel', 'hpanel', 'static', 'assets', 'cdn', 'demo',
    ];

    protected $fillable = [
        'name', 'slug', 'ruc', 'industry', 'currency', 'currency_symbol',
        'points_label', 'timezone', 'logo_path', 'primary_color', 'settings', 'status',
    ];

    /** Valores por defecto (igual que en la migración) */
    protected $attributes = [
        'currency' => 'PEN',
        'currency_symbol' => 'S/',
        'points_label' => 'Puntos',
        'timezone' => 'America/Lima',
        'primary_color' => '#4F46E5',
        'industry' => 'otro',
        'status' => self::STATUS_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Dirección del negocio: el slug es su subdominio.
     * Ej: slug "cafe-miraflores" + APP_DOMAIN "tudominio.com" => https://cafe-miraflores.tudominio.com
     */
    public function url(string $path = '/'): string
    {
        $request = request();
        $scheme = $request ? $request->getScheme() : parse_url(config('app.url'), PHP_URL_SCHEME);
        $port = $request?->getPort();
        $port = in_array($port, [null, 80, 443], true) ? '' : ':'.$port;

        return "{$scheme}://{$this->slug}.".config('app.domain').$port.'/'.ltrim($path, '/');
    }

    /** Formatea un monto con la moneda del negocio: S/ 1,250.50 */
    public function money(float|int|string|null $amount): string
    {
        return $this->currency_symbol.' '.number_format((float) $amount, 2, '.', ',');
    }
}
