<?php

namespace ZiffMedia\LaravelEloquentImagery\Controllers;

use Error;
use Exception;
use finfo;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Imagick;
use ImagickPixel;
use RuntimeException;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;
use ZiffMedia\LaravelEloquentImagery\ImageTransformer\ImageTransformer;
use ZiffMedia\LaravelEloquentImagery\UrlHandler\UrlHandler;

class EloquentImageryController extends Controller
{
    public function render(Request $request)
    {
        $path = $request->route('path');

        $cacheEnabled = config('eloquent-imagery.render.caching.enable', false);
        $cacheDriver = config('eloquent-imagery.render.caching.driver', 'disk');

        if ($cacheEnabled && Cache::has($path)) {
            return Cache::store($cacheDriver)->get($path);
        }

        // Path traversal detection: 404 the user, no need to give additional information
        abort_if(in_array($path[0], ['.', '/']) || str_contains($path, '../'), 404);

        $disk = config('eloquent-imagery.filesystem', config('filesystems.default'));

        /** @var Filesystem $filesystem */
        $filesystem = app(FilesystemManager::class)->disk($disk);

        $imageRequestData = app(UrlHandler::class)->getDataFromRequest($request);

        abort_if(! $imageRequestData->has('path'), 404);

        $imageActualPath = $imageRequestData->get('path');

        // step 1: if placeholder request, generate a placeholder
        if (config('eloquent-imagery.render.placeholder.enable') && $imageActualPath === config('eloquent-imagery.render.placeholder.filename')) {
            [$placeholderWidth, $placeholderHeight] = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];

            $imageBytes = $this->createPlaceHolderImage($placeholderWidth, $placeholderHeight);

            goto SERVE_BYTES;
        }

        $imageBytes = null;

        // step 2: no placeholder, look for actual file on designated filesystem
        if (config('eloquent-imagery.optimization.enable')) {
            $imageBytes = $filesystem->get($imageRequestData['optimized_path']);
        }

        if (! $imageBytes) {
            $imageBytes = $filesystem->get($imageActualPath);
        }

        if ($imageBytes) {
            $originalMimeType = config('eloquent-imagery.logging.enable')
                ? $this->getMimeTypeFromBytes($imageBytes) : null;

            goto SERVE_BYTES;
        }

        // step 3: no placeholder, no primary FS image, look for fallback image on alternative filesystem if enabled
        if (config('eloquent-imagery.render.fallback.enable')) {
            /** @var Filesystem $fallbackFilesystem */
            $fallbackFilesystem = app(FilesystemManager::class)->disk(config('eloquent-imagery.render.fallback.filesystem'));

            $imageBytes = $fallbackFilesystem->get($imageActualPath);

            if (config('eloquent-imagery.render.fallback.mark_images')) {
                $imageRequestData['fallbackbanner'] = true;
            }

            if ($imageBytes) {
                goto SERVE_BYTES;
            }
        }

        // step 4: no placeholder, no primary FS image, no fallback, generate a placeholder if enabled for missing files
        if (config('eloquent-imagery.render.placeholder.use_for_missing_files') === true) {
            [$placeholderWidth, $placeholderHeight] = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];

            $imageBytes = $this->createPlaceHolderImage($placeholderWidth, $placeholderHeight);

            goto SERVE_BYTES;
        }

        // There are no image bytes at this line, so we abort with 404
        abort(404);

        SERVE_BYTES:

        $imageBytes = app(ImageTransformer::class)->transform($imageRequestData, $imageBytes);

        // @todo determine mime type
        $mimeType = $this->getMimeTypeFromBytes($imageBytes);

        $browserCacheMaxAge = config('eloquent-imagery.render.browser_cache_max_age');

        if (! in_array($mimeType, ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp', 'image/tiff'])
            && config('eloquent-imagery.logging.enable', false)
        ) {
            // log original mime type if it was different from the current mime type
            logger()->info("Unsupported image mime type served $mimeType", ['original_mime_type' => $originalMimeType ?? 'unknown']);
        }

        $response = response($imageBytes, headers: ['Content-Type' => $mimeType, 'Cache-Control' => "public, max-age=$browserCacheMaxAge"]);

        if ($cacheEnabled) {
            Cache::store($cacheDriver)->put($path, $response, config('eloquent-imagery.render.caching.ttl', 60));
        }

        return $response;
    }

    protected function getMimeTypeFromBytes($bytes): false|string
    {
        static $fInfo = null;

        if (! $fInfo) {
            $fInfo = new finfo;
        }

        return $fInfo->buffer($bytes, FILEINFO_MIME_TYPE);
    }

    protected function createPlaceHolderImage($width, $height, $backgroundColor = 'FFFFFF'): string
    {
        if (! class_exists('Imagick')) {
            throw new RuntimeException('Imagick is not installed, a placeholder cannot be created.');
        }

        try {
            $image = new Imagick();
            $image->newImage($width, $height, new ImagickPixel('red'));
            $image->setImageFormat('png');

            return $image->getImageBlob();
        } catch (Exception $e) {
            if (config('eloquent-imagery.logging.enable') === true) {
                logger()->error('Imagick error while creating placeholder image: '.$e->getMessage());
            }
        }

        // @todo placeholders from configured image
        return '';
    }
}
