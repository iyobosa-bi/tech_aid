<?php

namespace App\Providers;

use App\View\Composers\NotificationBellComposer;
use Illuminate\Support\Facades\View;
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
        // The notification bell in the app layout's topbars (Flow 10).
        View::composer('layouts.app', NotificationBellComposer::class);
    }
}
