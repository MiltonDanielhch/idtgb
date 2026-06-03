<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use App\Models\Tramite;
use App\Models\Pago;
use App\Observers\TramiteObserver;
use App\Observers\PagoObserver;
use App\Services\DashboardCacheInvalidator;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DashboardCacheInvalidator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Forzar HTTPS si estamos detrás de un proxy (Coolify/Nginx)
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            URL::forceScheme('https');
        }

        // 2. Configurar el Paginador para Bootstrap
        Paginator::useBootstrap();

        // 3. Sintonía de Tiempo: Asegurar que Carbon use la zona horaria de Bolivia
        // Esto es vital para los cálculos de intereses en el Beni
        Carbon::setLocale('es');
        date_default_timezone_set('America/La_Paz');

        // 4. Registro de Observers para la gestión de caché y Dashboard
        Tramite::observe(TramiteObserver::class);
        Pago::observe(PagoObserver::class);

        // 5. Optimizaciones de rendimiento para producción (Protegidas para CLI/Artisan)
        if (app()->environment('production')) {

            // Habilitar compresión de respuesta SOLO si no es una ejecución de consola
            if (!app()->runningInConsole() && function_exists('ob_gzhandler')) {
                // Verificar que no se haya inicializado el manejador previamente
                if (!in_array('ob_gzhandler', ob_get_status(true))) {
                    ob_start('ob_gzhandler');
                }
            }

        }
    }
}
