<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ConferencePageController;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // Protected API routes
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Public API routes - only use basic API middleware without auth
            Route::middleware(['throttle:api', 'bindings'])
                ->prefix('api')
                ->group(function () {
                    Route::get('conferences/{conference}/pages', [ConferencePageController::class, 'show']);
                    Route::get('health', function () {
                        return response()->json(['status' => 'ok', 'message' => 'API is online'], 200);
                    });
                });

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
                
            if (file_exists(base_path('routes/health.php'))) {
                Route::middleware('web')
                    ->group(base_path('routes/health.php'));
            }
            
            if (file_exists(base_path('routes/console.php'))) {
                Route::middleware('web')
                    ->group(base_path('routes/console.php'));
            }
        });
    }
} 