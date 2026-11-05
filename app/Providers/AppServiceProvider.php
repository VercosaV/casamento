<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use App\Models\Secao;
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
        Paginator::useBootstrapFive();
        RateLimiter::for('rsvp', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // O menu do topo é montado com as seções que os noivos deixaram visíveis.
        View::composer('layouts.app', function ($view) {
            $view->with('menuSecoes', Secao::noMenu()->get());
            $view->with('secaoInicio', Secao::where('tipo', 'inicio')->first());
        });

    }
}
