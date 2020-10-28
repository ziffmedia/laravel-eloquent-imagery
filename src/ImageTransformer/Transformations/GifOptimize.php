<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;
use ImagickException;

class GifOptimize implements ImagickTransformationInterface
{
    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if ($imagick->getImageFormat() !== 'GIF' || $imagick->getNumberImages() < 2) {
            return;
        }

        try {
            $imagick->optimizeImageLayers();
            $imagick->quantizeImages(256, Imagick::COLORSPACE_RGB, 8, false, false);
        } catch (ImagickException $e) {
            logger()->error('Caught ImagickException: ' . $e->getMessage());
        }
    }
}
