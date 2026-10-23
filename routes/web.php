<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Super\BusinessController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
| Las mismas rutas sirven al dominio principal y a los subdominios.
| IdentifyBusinessByHost decide de qué negocio es la petición:
|   tudominio.com          → Super Admin (rutas 'super')
|   cafe.tudominio.com     → negocio "cafe" (rutas 'tenant')
*/

Route::get('/manifest.webmanifest', ManifestController::class)->name('manifest');

// ---------- Invitados ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', [HomeController::class, 'redirect'])->name('home');

    // ---------- Usuarios de un negocio (admin / cajero) ----------
    Route::middleware('tenant')->group(function () {
        Route::get('/panel', [HomeController::class, 'dashboard'])
            ->middleware('can:dashboard.ver')->name('dashboard');

        Route::middleware('can:sucursales.gestionar')->group(function () {
            Route::get('/sucursales', [BranchController::class, 'index'])->name('branches.index');
            Route::post('/sucursales', [BranchController::class, 'store'])->name('branches.store');
            Route::put('/sucursales/{branch}', [BranchController::class, 'update'])->name('branches.update');
            Route::patch('/sucursales/{branch}/estado', [BranchController::class, 'toggle'])->name('branches.toggle');
        });

        Route::middleware('can:usuarios.gestionar')->group(function () {
            Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
            Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
            Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggle'])->name('users.toggle');
        });

        Route::middleware('can:configuracion.editar')->group(function () {
            Route::get('/configuracion', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/configuracion', [SettingsController::class, 'update'])->name('settings.update');
        });
    });

    // ---------- Super Admin (dueño de la plataforma) ----------
    Route::middleware('super')->prefix('super')->name('super.')->group(function () {
        Route::get('/negocios', [BusinessController::class, 'index'])->name('businesses.index');
        Route::post('/negocios', [BusinessController::class, 'store'])->name('businesses.store');
        Route::patch('/negocios/{business}/estado', [BusinessController::class, 'toggle'])->name('businesses.toggle');
    });
});
