<?php

namespace ZiffMedia\LaravelEloquentImagery\UrlHandler\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;

class LegacyStrategy implements StrategyInterface
{
    protected $urlModifierRegexes = [
        'width'      => '/^size_(?P<value>\d*){0,1}x(?:\d*){0,1}$/', // set width
        'height'     => '/^size_(?:\d*){0,1}x(?P<value>\d*){0,1}$/', // set height
        'fit'        => '/^fit_(?P<value>[a-z]+)$/', // set height
        'grayscale'  => '/^grayscale$/', // grayscale
        'quality'    => '/^quality_(?P<value>[0-9]+)/', //quality, if applicable
        'background' => '/^bg_(?P<value>[\da-f]{6})$/', // background hex
        'trim'       => '/^trim_(?P<value>\d+)$/', // trim, tolerance
        'crop'       => '/^crop_(?P<value>[\dx]+)$/', // crop operations
        'fill'       => '/^fill$/', // fill operation
        'gravity'    => '/^gravity_(?P<value>[\w_]+)$/', // optional gravity param, g_auto - means center, g_north or g_south
        'static'     => '/^static(?:_(?P<value>\d*)){0,1}$/', // ensure even animated gifs are single frame
    ];

    protected string $extensionsRegex;

    public function __construct()
    {
        $this->extensionsRegex = '(' . implode('|', Image::MIME_TYPE_EXTENSIONS) . ')';
    }

    public function getDataFromRequest(Request $request): Collection
    {
        $path = $request->route('path');
        $imageRequestData = new Collection();

        $pathInfo = pathinfo($path);
        $imagePath = $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] . '/' : '';

        $filenameWithoutExtension = $pathInfo['filename'];
        $actualExtension = $pathInfo['extension'];

        // does it still have an extension?
        if (preg_match("#\\.{$this->extensionsRegex}$#", $filenameWithoutExtension, $matches)) {
            $filenameWithoutExtension = Str::replaceLast($matches[0], '', $filenameWithoutExtension);

            if ($matches[1] !== $pathInfo['extension']) {
                $imageRequestData['convert'] = $pathInfo['extension'];
                $actualExtension = $matches[1];
            }
        }

        if (str_contains($filenameWithoutExtension, '.')) {
            $filenameParts = explode('.', $filenameWithoutExtension);
            $filenameWithoutExtension = $filenameParts[0];

            $modifierSpecs = array_slice($filenameParts, 1);

            foreach ($modifierSpecs as $modifierSpec) {
                $matches = [];
                foreach ($this->urlModifierRegexes as $modifier => $regex) {
                    if (preg_match($regex, $modifierSpec, $matches)) {
                        $imageRequestData[$modifier] = $matches['value'] ?? true;
                    }
                }
            }
        }

        $imagePath .= "{$filenameWithoutExtension}.{$actualExtension}";

        $imageRequestData['path'] = $imagePath;
        $imageRequestData['optimized_path'] = Str::replaceLast($actualExtension, "optimized.{$actualExtension}", $imagePath);

        if (isset($imageRequestData['fit']) && $imageRequestData['fit'] === 'lim') {
            $imageRequestData['fit'] = 'limit';
        }

        return $imageRequestData;
    }

    public function toUrl(Image $image, Collection $transformations = null)
    {
        // handle size, width, height
        if ($transformations->has('size')) {
            unset($transformations['width'], $transformations['height']);
        } elseif ($transformations->has('width') || $transformations->has('height')) {
            $transformations['size'] =
                ($transformations['width'] ?? '')
                . 'x'
                . ($transformations['height'] ?? '');

            unset($transformations['width'], $transformations['height']);
        }

        // handle versioning
        if ($version = $transformations->search(function ($value, $key) {
            return preg_match('/^v\d/', $key);
        })) {
            unset($transformations[$version]);
        }

        if (! $version && $transformations->has('version') || $transformations->has('v')) {
            $version = $transformations->get('v', $transformations->get('version'));
        }

        if ($version) {
            // dynamic call, localize this
            $imageTimestamp = $image->timestamp;

            if (is_bool($version) && $imageTimestamp) {
                $version = 'v' . $imageTimestamp;
            } elseif (is_string($version) && strpos($version, '{timestamp}') !== false && $imageTimestamp) {
                $version = str_replace('{timestamp}', $imageTimestamp, $version);
            }

            unset($transformations['version'], $transformations['v']);

            $transformations['version'] = $version;
        }

        // @todo check next 12 lines
        // keyed with [dirname, filename, basename, extension]
        $pathinfo = pathinfo($image->path);

        if (! isset($pathinfo['dirname'])) {
            throw new InvalidArgumentException("pathinfo() was unable to parse {$image->path} into path parts.");
        }

        $extension = $pathinfo['extension'];
        if ($transformations->has('convert')) {
            if ($pathinfo['extension'] != $transformations['convert']) {
                $extension = $extension . '.' . $transformations['convert'];
            }
            unset($transformations['convert']);
        }

        $transformations = $transformations->map(function ($value, $key) {
            if ($key === 'version') {
                return $value;
            }

            if ($value === true) {
                return $key;
            }

            return $key . '_' . $value;
        })->sort()->implode('.');

        // keyed with [dirname, filename, basename, extension]
        $pathinfo = pathinfo($image->path);

        if (! isset($pathinfo['dirname'])) {
            throw new InvalidArgumentException("pathinfo() was unable to parse {$image->path} into path parts.");
        }

        $pathWithModifiers =
            (($pathinfo['dirname'] !== '.') ? "{$pathinfo['dirname']}/" : '')
            . $pathinfo['filename']
            . ($transformations ? ".{$transformations}" : '')
            . ".{$extension}";

        return url()->route('eloquent-imagery.render', $pathWithModifiers);
    }
}
