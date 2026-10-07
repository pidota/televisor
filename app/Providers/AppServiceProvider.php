<?php

namespace App\Providers;

use App\Enums\AssigneeType;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\ScreenGroup;
use App\Models\UrgentMessage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Relation::enforceMorphMap([
            AssigneeType::Screen->value => Screen::class,
            AssigneeType::ScreenGroup->value => ScreenGroup::class,
            'media_asset' => MediaAsset::class,
            'playlist' => Playlist::class,
            'schedule' => Schedule::class,
            'urgent_message' => UrgentMessage::class,
        ]);

        Paginator::useBootstrapFive();
        Carbon::setLocale(config('app.locale', 'es'));

        Gate::define('manage-users', fn ($user) => $user->hasRole('admin'));
        Gate::define('manage-settings', fn ($user) => $user->hasRole('admin'));
        Gate::define('manage-content', fn ($user) => $user->hasAnyRole(['admin', 'operator']));

        RateLimiter::for('api-device-pair', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('api-device', function (Request $request) {
            $screen = $request->attributes->get('device_screen');
            $key = $screen?->id ? 'screen:'.$screen->id : $request->ip();

            return Limit::perMinute(180)->by($key);
        });
    }
}
