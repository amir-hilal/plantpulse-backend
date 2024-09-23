<?php

namespace App\Providers;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        Schema::defaultStringLength(191);
        DB::listen(function ($query) {
        // Log the query and its bindings
        Log::info('Query: ' . $query->sql);
        Log::info('Bindings: ' . implode(', ', $query->bindings));
        Log::info('Time: ' . $query->time . ' ms');
    	});
   }
}
