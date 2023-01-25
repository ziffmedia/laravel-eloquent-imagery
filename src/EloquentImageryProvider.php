<?php

namespace ZiffMedia\LaravelEloquentImagery;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use ZiffMedia\LaravelEloquentImagery\ImageTransformer\ImageTransformer;
use ZiffMedia\LaravelEloquentImagery\UrlHandler\UrlHandler;

class EloquentImageryProvider extends ServiceProvider
{
    protected string $packageConfigPath;

    public function register()
    {
        $this->packageConfigPath = realpath(__DIR__ . '/../config/eloquent-imagery.php');

        // setup configuration, merge values from top level config
        $this->mergeConfigFrom($this->packageConfigPath, 'eloquent-imagery');

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

    public function boot(Router $router)
    {
        // publish the configuration in cli local environment
        if ($this->app->runningInConsole() && $this->app->environment('local')) {
            $this->publishes([$this->packageConfigPath => config_path('eloquent-imagery.php')], 'config');
        }

        // ensure this config key is null, since it is computed
        config(['eloquent-imagery.extension' => null]);

        foreach ((array) config('eloquent-imagery.extension_priority') as $extension) {
            if (extension_loaded($extension)) {
                config(['eloquent-imagery.extension' => $extension]);

                break;
            }
        }

        if (config('eloquent-imagery.render.enable')) {
            if (! $this->app->runningInConsole() && !config('eloquent-imagery.extension')) {
                throw new RuntimeException('Eloquent Imagery requires ext/ImageMagick or ext/gd in order to render images');
            }

            $imageRoute = rtrim(config('eloquent-imagery.render.route', '/imagery'), '/');

            $router->get("{$imageRoute}/{path}", Controllers\EloquentImageryController::class . '@render')
                ->where('path', '(.*)')
                ->name('eloquent-imagery.render')
                ->domain(config('eloquent-imagery.render.domain', null));

            Blade::directive('placeholderImageUrl', [View\BladeDirectives::class, 'placeholderImageUrl']);
        }
    }
}
