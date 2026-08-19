<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PhotoStorageInterface;
use App\Contracts\ProfileCacheServiceInterface;
use App\Events\User\UserProfileChanged;
use App\Listeners\User\InvalidateProfileCache;
use App\Services\LocalPhotoStorage;
use App\Services\PhotoService;
use App\Services\ProfileCacheService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(PhotoService::class)
            ->needs(PhotoStorageInterface::class)
            ->give(fn ($app): LocalPhotoStorage => new LocalPhotoStorage(
                filesystem: $app->make(FilesystemFactory::class),
                disk: config('filesystems.photo_disk', 'public'),
                directory: config('filesystems.photo_dir', 'photos'),
            ));

        $this->app->bind(
            ProfileCacheServiceInterface::class,
            ProfileCacheService::class,
        );
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        Event::listen(UserProfileChanged::class, InvalidateProfileCache::class);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('authenticated', fn (Request $request) => $request->user()
            ? Limit::perMinute(120)->by($request->user()->id)
            : Limit::perMinute(60)->by($request->ip()));
    }
}
