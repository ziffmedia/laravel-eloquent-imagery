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
        if (!preg_match('#(?P<width>\d+)x(?P<height>\d+)#', $crop, $matches)) {
            return;
        }

        [$width, $height] = [$matches['width'], $matches['height']];

//        [$width, $height] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        foreach ($imagick as $image) {
            $image->cropImage($width, $height, 0, 0);
        }
    }
}

