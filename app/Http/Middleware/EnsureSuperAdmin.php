<?php

namespace App\Http\Middleware;

use App\Support\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Panel de la plataforma: solo en el dominio principal y solo para Super Admin */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(app(CurrentBusiness::class)->get() !== null, 404);
        abort_unless($request->user()?->isSuperAdmin() && $request->user()->is_active, 403);

        return $next($request);
    }
}
