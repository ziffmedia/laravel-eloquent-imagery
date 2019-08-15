<?php

namespace ZiffMedia\Laravel\EloquentImagery;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Image as InterventionImage;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaCoreServiceProvider;
use RuntimeException;

class EloquentImageryProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        if (config()->has('eloquent_imagery')) {
            throw new RuntimeException('It appears you have updated laravel-eloquent-imagery to a version >=0.5.0, please refer to the upgrade guide to upgrade your code');
        }

        $packageConfigPath = realpath(__DIR__ . '/../config/eloquent-imagery.php');
        $this->mergeConfigFrom($packageConfigPath, 'eloquent-imagery');

        // publish the configuration in cli local environment
        if ($this->app->runningInConsole() && $this->app->environment('local')) {
            $this->publishes([$packageConfigPath => config_path('eloquent-imagery.php')], 'config');
        }

        if (config('eloquent-imagery.render.enable')) {
            if (!$this->app->runningInConsole() && !extension_loaded('imagick') && !class_exists(InterventionImage::class)) {
                throw new RuntimeException('Eloquent Imagery requires ext/ImageMagick and intervention/image package in order to render images');
            }

            $imageRoute = rtrim(config('eloquent-imagery.render.route', '/imagery'), '/');

            $router->get("{$imageRoute}/{path}", Controller\EloquentImageryController::class . '@render')
                ->where('path', '(.*)')
                ->name('eloquent-imagery.render')
                ->domain(config('eloquent-imagery.render.domain', null));

            Blade::directive('placeholderImageUrl', [View\BladeDirectives::class, 'placeholderImageUrl']);
        }

        if ($this->app->getProviders(NovaCoreServiceProvider::class)) {
            Nova::serving(function (ServingNova $event) {
                Nova::script('eloquent-imagery', __DIR__ . '/../dist/js/nova.js');
            });
        }
    }
}
