<?php

namespace App\Providers;

use App\Services\GraphService;
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
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
