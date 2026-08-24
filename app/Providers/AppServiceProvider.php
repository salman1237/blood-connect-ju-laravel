<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
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
        // Flat JSON everywhere the API returns a resource — a resource
        // returned directly from a controller (e.g. AuthController::me())
        // would otherwise be the only endpoint wrapped in a "data" key,
        // while resources nested inside a manual response()->json([...])
        // array (register/login's "user" field) never are. One rule avoids
        // the Android client needing to special-case any one endpoint.
        JsonResource::withoutWrapping();
    }
}
