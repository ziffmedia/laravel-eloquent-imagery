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

        $imagick->setImageFormat($arguments->get('convert'));
    }
}

