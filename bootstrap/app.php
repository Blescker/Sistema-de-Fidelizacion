<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\IdentifyBusinessByHost;
use App\Http\Middleware\SetCurrentBusiness;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => SetCurrentBusiness::class,
            'super' => EnsureSuperAdmin::class,
        ]);

        // Lo primero de cada petición: identificar el negocio por el subdominio.
        // Así el BusinessScope ya filtra al cargar el usuario y los {modelos} de la URL.
        $middleware->web(prepend: [IdentifyBusinessByHost::class]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Para peticiones AJAX: sesión vencida => JSON 401 con mensaje en español
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
            }
        });
    })->create();
