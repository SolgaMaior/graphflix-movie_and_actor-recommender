<?php

namespace App\Providers;

use App\Services\GraphService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GraphService::class, function (): GraphService {
            return new GraphService(
                config('services.cognodb.uri'),
                config('services.cognodb.user'),
                config('services.cognodb.password'),
                config('services.cognodb.retries', 3),
                config('services.cognodb.retry_delay_ms', 250),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
