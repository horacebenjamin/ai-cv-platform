<?php

namespace App\Providers;

use App\Services\AI\AIProviderInterface;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AIProviderInterface::class, function ($app): AIProviderInterface {
            $provider = config('ai.default_provider');
            $driver = config("ai.providers.{$provider}.legacy_driver", config("ai.providers.{$provider}.driver"));

            return $app->make($driver);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
