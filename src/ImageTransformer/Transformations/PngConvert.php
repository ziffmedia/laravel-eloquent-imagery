<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class PngConvert implements ImagickTransformationInterface
{
    protected $strip = true;

    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('pngconvert')) {
            return;
        }
        if ($imagick->getImageFormat() != 'png') {
            $imagick->setImageFormat('png');
        }

    }
}

