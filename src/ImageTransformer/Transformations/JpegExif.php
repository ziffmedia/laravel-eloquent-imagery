<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class JpegExif implements ImagickTransformationInterface
{
    protected $strip = true;

    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if ($this->strip) {
            $imagick->stripImage();
        }
    }
}

