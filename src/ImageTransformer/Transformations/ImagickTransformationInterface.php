<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

interface ImagickTransformationInterface
{
    public function applyImagick(Collection $arguments, Imagick $imagick);
}
