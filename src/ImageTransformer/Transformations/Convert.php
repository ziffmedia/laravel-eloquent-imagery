<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;

class Convert implements ImagickTransformationInterface
{
    protected $strip = true;

    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('convert')) {
            return;
        }

        $currentFormat = strtolower($imagick->getImageFormat());
        if (in_array($currentFormat, Image::SUPPORTED_FORMATS)
            && in_array($arguments['convert'], Image::SUPPORTED_FORMATS)
            && $currentFormat != $arguments['convert']) {
            $imagick->setImageFormat($arguments['convert']);
        }

    }
}

