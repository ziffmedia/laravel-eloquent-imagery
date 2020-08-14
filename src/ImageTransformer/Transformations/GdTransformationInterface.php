<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;

interface GdTransformationInterface
{
    public function applyGd(Collection $arguments, $gdResource);
}
