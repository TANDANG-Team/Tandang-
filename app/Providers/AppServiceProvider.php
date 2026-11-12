<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('database.default') === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            if ($database && $database !== ':memory:' && is_string($database) && ! file_exists($database)) {
                $directory = dirname($database);
                if (is_dir($directory) && is_writable($directory)) {
                    touch($database);
                }
            }
        }
    }
}
