<?php

namespace App\Providers;
use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        View::composer('partials.create-order-sheet', function ($view) {
            $view->with('tables', \App\Models\Table::where('is_active', true)->with('type')->orderBy('name')->get());
        });
        Order::observe(OrderObserver::class);
    }
}
