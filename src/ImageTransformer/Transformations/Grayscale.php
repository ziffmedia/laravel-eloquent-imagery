<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class Grayscale implements ImagickTransformationInterface
{
    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('grayscale')) {
            return;
        }

        foreach ($imagick as $image) {
            $image->setImageColorspace(Imagick::COLORSPACE_GRAY);
        }
    }
}

