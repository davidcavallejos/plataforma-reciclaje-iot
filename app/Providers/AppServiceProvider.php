<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Esp32Service;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // El servicio se construye desde config/services.php, así el resto de
        // la aplicación solo pide Esp32Service por inyección de dependencias.
        $this->app->bind(Esp32Service::class, fn () => Esp32Service::fromConfig());
    }

    public function boot(): void
    {
        // En producción local (red del taller) se sirve por HTTP.
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }
    }
}
