<?php

namespace ZiffMedia\LaravelEloquentImagery;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaCoreServiceProvider;
use RuntimeException;
use ZiffMedia\LaravelEloquentImagery\ImageTransformer\ImageTransformer;
use ZiffMedia\LaravelEloquentImagery\UrlHandler\UrlHandler;

class EloquentImageryProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        // @todo remove at 1.0 time
        if (config()->has('eloquent_imagery')) {
            throw new RuntimeException('It appears you have updated laravel-eloquent-imagery to a version >=0.5.0, please refer to the upgrade guide to upgrade your code');
        }

        // setup configuration, merge values from top level
        $packageConfigPath = realpath(__DIR__ . '/../config/eloquent-imagery.php');
        $this->mergeConfigFrom($packageConfigPath, 'eloquent-imagery');

        // publish the configuration in cli local environment
        if ($this->app->runningInConsole() && $this->app->environment('local')) {
            $this->publishes([$packageConfigPath => config_path('eloquent-imagery.php')], 'config');
        }

        if (config('eloquent-imagery.render.enable')) {
            if (!$this->app->runningInConsole() && !extension_loaded('imagick')) {
                throw new RuntimeException('Eloquent Imagery requires ext/ImageMagick in order to render images');
            }

            $imageRoute = rtrim(config('eloquent-imagery.render.route', '/imagery'), '/');

            $router->get("{$imageRoute}/{path}", Controllers\EloquentImageryController::class . '@render')
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

    public function register()
    {
        $this->app->singleton(ImageTransformer::class, function ($app) {
            return new ImageTransformer(ImageTransformer::createTransformationCollection(
                config('eloquent-imagery.render.transformation.transformers', [])
            ));
        });

        $this->app->singleton(UrlHandler::class, function ($app) {
            return new UrlHandler(UrlHandler::createStrategy(
                config('eloquent-imagery.urls.strategy', 'legacy')
            ));
        });
    }
}
