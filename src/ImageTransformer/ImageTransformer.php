<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer;

use Exception;
use Illuminate\Support\Collection;
use Imagick;
use ImagickException;
use InvalidArgumentException;
use RuntimeException;

class ImageTransformer
{
    const BUILTIN_TRANSFORMATIONS = [
        'crop'           => Transformations\Crop::class,
        'fill'           => Transformations\Fill::class,
        'fallbackbanner' => Transformations\FallbackBanner::class,
        'fit'            => Transformations\Fit::class,
        'gifoptimize'    => Transformations\GifOptimize::class,
        'gifstatic'      => Transformations\GifStatic::class,
        'grayscale'      => Transformations\Grayscale::class,
        'jpegexif'       => Transformations\JpegExif::class,
        'jpegnormalize'  => Transformations\JpegNormalize::class,
        'quality'        => Transformations\Quality::class,
        'convert'        => Transformations\Convert::class,
    ];

    public Collection $transformations;

    protected readonly string $extension;

    public static function createTransformationCollection($transformerConfigs): Collection
    {
        $transformers = new Collection;

        foreach ($transformerConfigs as $transformerConfig) {
            // set/reset name and args each iteration
            $name = $args = null;

            if (is_string($transformerConfig)) {
                [$name, $args] = [$transformerConfig, []];
            } elseif (is_array($transformerConfig)) {
                [$name, $args] = [
                    $transformerConfig['name'] ?? null,
                    $transformerConfig['with'] ?? null,
                ];
            }

            if (in_array($name, array_keys(static::BUILTIN_TRANSFORMATIONS))) {
                $transformers->push(
                    app(static::BUILTIN_TRANSFORMATIONS[$name], $args)
                );

                continue;
            }

            if (class_exists($name) &&
                ($name instanceof Transformations\ImagickTransformationInterface
                || $name instanceof Transformations\GdTransformationInterface)) {
                $transformers->push(
                    app($name, $args)
                );

                continue;
            }

            if (is_null($name)) {
                throw new InvalidArgumentException('Provided configuration for transforms does not have a valid transformerName');
            }
        }

        return $transformers;
    }

    public function __construct(Collection $transformations)
    {
        $this->transformations = $transformations;

        $extensions = (array) config('eloquent-imagery.extension_priority');

        foreach ($extensions as $extension) {
            if (extension_loaded($extension)) {
                $this->extension = $extension;

                break;
            }
        }

        if (! $this->extension) {
            throw new RuntimeException('No valid image library was found in php, tried: ' . implode(', ', $extensions));
        }
    }

    public function transform(Collection $arguments, $imageBytes): string
    {
        if ($this->extension !== 'imagick') {
            throw new RuntimeException('Currently only imagick is supported');
        }

        // normalize background for imagick
        if ($arguments->has('background')) {
            $background = $arguments->get('background');

            if (preg_match('/^[A-Fa-f0-9]{3,6}$/', $background)) {
                $arguments['background'] = '#' . $background;
            }
        }

        try {
            $imagick = new Imagick();
            $imagick->readImageBlob($imageBytes);

            $isCoalesced = false;

            if ($imagick->getImageFormat() === 'GIF' && $imagick->getNumberImages() > 1) {
                $imagick = $imagick->coalesceImages();

                $isCoalesced = true;
            }

            $this->transformations
                ->whereInstanceOf(Transformations\ImagickTransformationInterface::class)
                ->each(function ($transformation) use ($arguments, $imagick) {
                    $transformation->applyImagick($arguments, $imagick);
                });

            if ($isCoalesced) {
                $imagick = $imagick->deconstructImages();
            }
        } catch (ImagickException $imagickException) {
            // throw?
        } catch (Exception $exception) {
            // throw?
        }

        return $imagick->getImagesBlob();
    }
}
