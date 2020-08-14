<?php

use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\FilesystemManager;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;
use ZiffMedia\LaravelEloquentImagery\UrlHandler\UrlHandler;

if (! function_exists('eloquent_imagery_url')) {

    /**
     * Apply transformations to a url
     * @param string $relativePath
     * @param array $transformations
     * @return string
     * @deprecated
     */
    function eloquent_imagery_url($relativePath, string $transformations = '') {
        static $renderRouteEnabled = null;
        static $imageryFilesystem = null;

        if ($renderRouteEnabled === null) {
            config('eloquent-imagery.render.enable');
        }

        if ($imageryFilesystem === null) {
            $imageryFilesystem = app(FilesystemManager::class)->disk(config('eloquent-imagery.filesystem', config('filesystems.default')));
        }

        if ($renderRouteEnabled === false && $transformations) {
            throw new RuntimeException('Cannot process render options unless the rendering route is enabled');
        }

        if ($renderRouteEnabled === false && $imageryFilesystem instanceof Cloud) {
            return $imageryFilesystem->url($relativePath);
        }

        $globalPresets = config('eloquent-imagery.urls.presets');

        $image = new Image($relativePath, []);

        $image->setStateFromAttributeData([
            'path' => $relativePath,
            'extension' => pathinfo($relativePath, PATHINFO_EXTENSION), // suffix of relative path?
        ]);

        $transformations = $globalPresets[$transformations]
            ?? $transformations;

        return app(UrlHandler::class)->createUrl($image, $transformations);
    }
}
