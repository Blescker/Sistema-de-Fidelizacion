<?php

namespace App\Providers;

use App\Models\User;
use App\Support\CurrentBusiness;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // "scoped": una instancia por petición (se reinicia en cada request / job)
        $this->app->scoped(CurrentBusiness::class);
    }

    public function boot(): void
    {
        // El Super Admin puede hacer todo
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        // $business disponible en todas las vistas
        View::composer('*', function ($view) {
            if (! array_key_exists('business', $view->getData())) {
                $view->with('business', app(CurrentBusiness::class)->get());
            }
        });
    }
}
