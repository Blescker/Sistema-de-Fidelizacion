<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\CurrentBusiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request, CurrentBusiness $current): JsonResponse
    {
        // Máx. 5 intentos por minuto por usuario + negocio + IP
        $key = $request->input('username').'|'.$request->getHost().'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'ok' => false,
                'message' => "Demasiados intentos. Espera {$seconds} segundos.",
            ], 429);
        }

        // En el subdominio de un negocio solo entran sus usuarios (lo filtra el BusinessScope);
        // en el dominio principal solo entra el Super Admin.
        $credentials = $request->only('username', 'password');
        if ($current->get() === null) {
            $credentials[] = fn ($query) => $query->whereNull('business_id')->where('is_super_admin', true);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key);

            return response()->json(['ok' => false, 'message' => 'Usuario o contraseña incorrectos.'], 422);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return response()->json(['ok' => false, 'message' => 'Tu usuario está desactivado.'], 403);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'ok' => true,
            'message' => "¡Hola, {$user->name}!",
            'data' => ['redirect' => route('home')],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
