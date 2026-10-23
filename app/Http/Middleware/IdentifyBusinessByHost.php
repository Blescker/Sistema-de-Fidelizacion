<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Support\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Primer paso de TODA petición web: mira la dirección para saber de qué negocio es.
 *
 *   tudominio.com / www.tudominio.com  → plataforma (Super Admin), sin negocio
 *   cafe-miraflores.tudominio.com      → negocio con slug "cafe-miraflores"
 *   subdominio que no existe           → 404
 *   negocio suspendido                 → página de "servicio suspendido"
 */
class IdentifyBusinessByHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = null;
        $subdomain = $this->subdomain($request->getHost());

        if ($subdomain !== null) {
            $business = Business::where('slug', $subdomain)->first();

            abort_if($business === null, 404, 'Este negocio no existe.');

            if (! $business->isActive()) {
                return response()->view('errors.suspended', ['business' => $business], 403);
            }
        }

        app(CurrentBusiness::class)->set($business);
        app(PermissionRegistrar::class)->setPermissionsTeamId($business?->id);

        return $next($request);
    }

    /** "cafe.tudominio.com" => "cafe";  "tudominio.com" / "www..." / otro host => null */
    private function subdomain(string $host): ?string
    {
        $domain = strtolower(config('app.domain'));
        $host = strtolower($host);

        if (! str_ends_with($host, '.'.$domain)) {
            return null;
        }

        $sub = substr($host, 0, -strlen('.'.$domain));

        return ($sub === '' || $sub === 'www') ? null : $sub;
    }
}
