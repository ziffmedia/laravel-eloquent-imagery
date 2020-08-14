<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class Crop implements ImagickTransformationInterface
{

    /**
     * @param Collection $arguments
     * @param Imagick $imagick
     */
    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('crop')) {
            return;
        }

        $crop = $arguments->get('crop');

        // crop command must be in the format \dx\d
        if (preg_match('#(?P<x>\d)x(?P<y>\d)#', $crop, $matches)) {
            return;
        }

        [$x, $y] = [$matches['x'], $matches['y']];

        [$width, $height] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        foreach ($imagick as $image) {
            $image->cropImage($width, $height, $x, $y);
        }
    }
}

