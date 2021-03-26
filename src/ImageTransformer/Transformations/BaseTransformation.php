<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;

abstract class BaseTransformation implements ImagickTransformationInterface
{
    protected function getGravityParam(Collection $arguments): array
    {
        return explode('_', $arguments->get('gravity', 'auto'));
    }

    protected function getGravityYValue($gravity, $imgHeight, $targetHeight): int
    {
        if (in_array('north', $gravity)) {
            return 0;
        }
        if (in_array('south', $gravity)) {
            return $imgHeight - $targetHeight;
        }
        return (int)(($imgHeight - $targetHeight) / 2);
    }

    protected function getGravityXValue($gravity, $imgWidth, $targetWidth): int
    {
        if (in_array('west', $gravity)) {
            return 0;
        }
        if (in_array('east', $gravity)) {
            return $imgWidth - $targetWidth;
        }
        return (int)(($imgWidth - $targetWidth) / 2);
    }
}