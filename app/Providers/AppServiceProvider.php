<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PhotoStorageInterface;
use App\Services\LocalPhotoStorage;
use App\Services\PhotoService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\Request;
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
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
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
