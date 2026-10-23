<?php

namespace App\Http\Middleware;

use App\Support\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Para las pantallas de un negocio (rutas con middleware 'tenant'):
 *  1. Deben abrirse desde el subdominio de un negocio (no desde el dominio principal).
 *  2. El usuario logueado debe pertenecer a ESE negocio y estar activo.
 *
 * El negocio ya lo identificó IdentifyBusinessByHost a partir de la dirección.
 */
class SetCurrentBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = app(CurrentBusiness::class)->get();
        $user = $request->user();

        abort_if($business === null, 404);

        if (! $user || (int) $user->business_id !== $business->id || ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user && ! $user->is_active ? 'Tu usuario está desactivado.' : 'Inicia sesión para continuar.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $message], 401)
                : redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}
