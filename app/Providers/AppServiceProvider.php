<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Sub-directory deploy (e.g. https://golden.msu.ac.th/msuir): when APP_URL
        // carries a path, force every generated URL (route(), asset(), Ziggy) to
        // include it. Local dev APP_URL (http://localhost:8000) has no path → skipped.
        $appUrl = (string) config('app.url');
        $path = trim((string) parse_url($appUrl, PHP_URL_PATH), '/');

        if ($path !== '') {
            URL::forceRootUrl($appUrl);
        }

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
