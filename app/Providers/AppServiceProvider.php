<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Pagination\Paginator;   
use Illuminate\Support\Facades\URL;
use App\Models\Tramite;
use App\Models\Pago;
use App\Observers\TramiteObserver;
use App\Observers\PagoObserver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        // Detectar si estamos detrás de un proxy (como Coolify)
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', true);
        }

        Paginator::useBootstrap();

    // Registrar observers para invalidar cache del dashboard cuando haya cambios
    Tramite::observe(TramiteObserver::class);
    Pago::observe(PagoObserver::class);

    }
}
