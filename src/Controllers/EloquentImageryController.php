<?php

namespace ZiffMedia\LaravelEloquentImagery\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
        abort_if((in_array($path[0], ['.', '/']) || strpos($path, '../') !== false), 404);

        $disk = config('eloquent-imagery.filesystem', config('filesystems.default'));

        /** @var Filesystem $filesystem */
        $filesystem = app(FilesystemManager::class)->disk($disk);

        $imageRequestData = app(UrlHandler::class)->getDataFromRequest($request);

        $imageActualPath = $imageRequestData->get('path');

        // $imageRequest = ImageRequest::create();
        // $imageRequest->fillFromRequest($request);

        // assume the mime type is PNG unless otherwise specified
        $mimeType = 'image/png';
        $imageBytes = null;

        // step 1: if placeholder request, generate a placeholder
        // if ($filenameWithoutExtension === config('eloquent-imagery.render.placeholder.filename') && config('eloquent-imagery.render.placeholder.enable')) {
        if (
            config('eloquent-imagery.render.placeholder.enable')
            && $imageActualPath === config('eloquent-imagery.render.placeholder.filename')
        ) {
            list ($placeholderWidth, $placeholderHeight) = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];
            $imageBytes = $this->createPlaceHolderImage($imageRequestData);
        }

        // step 2: no placeholder, look for actual file on designated filesystem
        if (!$imageBytes) {
            try {
                $imageBytes = $filesystem->get($imageActualPath);
                $mimeType = $filesystem->getMimeType($imageActualPath);
            } catch (FileNotFoundException $e) {
                $imageBytes = null;
            }
        }

        // step 3: no placeholder, no primary FS image, look for fallback image on alternative filesystem if enabled
        if (!$imageBytes && config('eloquent-imagery.render.fallback.enable')) {
            /** @var Filesystem $fallbackFilesystem */
            $fallbackFilesystem = app(FilesystemManager::class)->disk(config('eloquent-imagery.render.fallback.filesystem'));
            try {
                $imageBytes = $fallbackFilesystem->get($imageActualPath);
                $mimeType = $fallbackFilesystem->getMimeType($imageActualPath);
                if (config('eloquent-imagery.render.fallback.mark_images')) {
                    $imageModifier = new ImageTransformer();
                    $imageBytes = $imageModifier->addFromFallbackWatermark($imageBytes);
                }
            } catch (FileNotFoundException $e) {
                $imageBytes = null;
            }
        }

        // step 4: no placeholder, no primary FS image, no fallback, generate a placeholder if enabled for missing files
        if (!$imageBytes && config('eloquent-imagery.render.placeholder.use_for_missing_files') === true) {
            list ($placeholderWidth, $placeholderHeight) = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];
            $imageBytes = $this->createPlaceHolderImage($imageRequestData);
        }

        abort_if(!$imageBytes, 404); // no image, no fallback, no placeholder

        $imageBytes = app(ImageTransformer::class)->transform($imageRequestData, $imageBytes);

        $browserCacheMaxAge = config('eloquent-imagery.render.browser_cache_max_age');

        $response = response()
            ->make($imageBytes)
            ->header('Content-type', $mimeType)
            ->header('Cache-control', "public, max-age=$browserCacheMaxAge");

        if ($cacheEnabled) {
            Cache::store($cacheDriver)->put($path, $response, config('eloquent-imagery.render.caching.ttl', 60));
        }

        return $response;
    }

    protected function createPlaceHolderImage(Collection $imageRequestData)
    {
        // $image = (new ImageManager(['driver' => 'imagick']))->canvas($width, $height, $backgroundColor);
        //
        // $image->text("{$width}x{$height}", $width / 2, $height / 2, function(AbstractFont $font) use ($width) {
        //     $font->align('center');
        //     $font->valign('middle');
        //     $font->color('000000');
        //     $font->file(__DIR__ . '/../../fonts/OverpassMono-Regular.ttf');
        //     $font->size(ceil((.9 * $width) / 7));
        // });
        //
        // return $image->encode('png')->__toString();
    }
}
