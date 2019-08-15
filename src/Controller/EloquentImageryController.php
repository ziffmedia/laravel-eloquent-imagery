<?php

namespace ZiffMedia\Laravel\EloquentImagery\Controller;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use ZiffMedia\Laravel\EloquentImagery\Image\ImageModifier;
use ZiffMedia\Laravel\EloquentImagery\Image\PlaceholderImageFactory;

class EloquentImageryController extends Controller
{
    /**
     * Parsers for url route modifiers
     *
     * @var array
     */
    protected $urlOperators = [
        'size'      => '/^size_([0-9]*x[0-9]*)$/', // set width
        'fit'       => '/^fit_([a-z]+)$/', // set height
        'grayscale' => '/^grayscale$/', // grayscale
        'quality'   => '/^quality_([0-9]+)/', //quality, if applicable
        'bgcolor'   => '/^bg_([\da-f]{6})$/', // background hex
        'trim'      => '/^trim_(\d+)$/', // trim, tolerance
        'crop'      => '/^crop_([\d,?]+)$/' // crop operations
    ];

    public function render($path)
    {
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

        $pathinfo = pathinfo($path);
        $storagePath = $pathinfo['dirname'] . '/';

        $modifierOperators = [];

        $filenameWithoutExtension = $pathinfo['filename'];

        if (strpos($filenameWithoutExtension, '.') !== false) {
            $filenameParts = explode(".", $filenameWithoutExtension);
            $filenameWithoutExtension = $filenameParts[0];
            $storagePath .= "{$filenameWithoutExtension}.{$pathinfo['extension']}";

            $modifierSpecs = array_slice($filenameParts, 1);

            foreach ($modifierSpecs as $modifierSpec) {
                $matches = [];
                foreach ($this->urlOperators as $operator => $regex) {
                    if (preg_match($regex, $modifierSpec, $matches)) {
                        $arg = null;
                        if (isset($matches[1])) {
                            $arg = $matches[1];
                        } else {
                            $arg = true;
                        }
                        $modifierOperators[$operator] = $arg;
                    }
                }
            }
        } else {
            $storagePath .= $pathinfo['basename'];
        }

        // assume the mime type is PNG unless otherwise specified
        $mimeType = 'image/png';
        $imageBytes = null;

        // step 1: if placeholder request, generate a placeholder
        if ($filenameWithoutExtension === config('eloquent-imagery.render.placeholder.filename') && config('eloquent-imagery.render.placeholder.enable')) {
            list ($placeholderWidth, $placeholderHeight) = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];
            $imageBytes = (new PlaceholderImageFactory())->create($placeholderWidth, $placeholderHeight, $modifierOperators['bgcolor'] ?? null);
        }

        // step 2: no placeholder, look for actual file on desiganted filesystem
        if (!$imageBytes) {
            try {
                $imageBytes = $filesystem->get($storagePath);
                $mimeType = $filesystem->getMimeType($storagePath);
            } catch (FileNotFoundException $e) {
                $imageBytes = null;
            }
        }

        // step 3: no placeholder, no primary FS image, look for fallback image on alternative filesystem if enabled
        if (!$imageBytes && config('eloquent-imagery.render.fallback.enable')) {
            /** @var Filesystem $fallbackFilesystem */
            $fallbackFilesystem = app(FilesystemManager::class)->disk(config('eloquent-imagery.render.fallback.filesystem'));
            try {
                $imageBytes = $fallbackFilesystem->get($storagePath);
                $mimeType = $fallbackFilesystem->getMimeType($storagePath);
                if (config('eloquent-imagery.render.fallback.mark_images')) {
                    $imageModifier = new ImageModifier();
                    $imageBytes = $imageModifier->addFromFallbackWatermark($imageBytes);
                }
            } catch (FileNotFoundException $e) {
                $imageBytes = null;
            }
        }

        // step 4: no placeholder, no primary FS image, no fallback, generate a placeholder if enabled for missing files
        if (!$imageBytes && config('eloquent-imagery.render.placeholder.use_for_missing_files') === true) {
            list ($placeholderWidth, $placeholderHeight) = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];
            $imageBytes = (new PlaceholderImageFactory())->create($placeholderWidth, $placeholderHeight, $modifierOperators['bgcolor'] ?? null);
        }

        abort_if(!$imageBytes, 404); // no image, no fallback, no placeholder

        $imageModifier = new ImageModifier();
        foreach ($modifierOperators as $operator => $arg) {
            call_user_func_array([$imageModifier, 'set' . ucfirst($operator)], [$arg]);
        }
        $imageBytes = $imageModifier->modify($imageBytes);

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
}
