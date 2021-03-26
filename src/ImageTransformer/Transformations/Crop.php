<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class Crop extends BaseTransformation
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
        if (!preg_match('#(?P<x>\d+)x(?P<y>\d+)#', $crop, $matches))
            return;
        [$targetWidth, $targetHeight] = [$matches['x'], $matches['y']];

        $gravity = $this->getGravityParam($arguments);
        [$imgWidth, $imgHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        $x = 0;
        $y = 0;

        if ($imgWidth > $targetWidth) {
            $x = $this->getGravityXValue($gravity, $imgWidth, $targetWidth);
        }

        if ($imgHeight > $targetHeight) {
            $y = $this->getGravityYValue($gravity, $imgHeight, $targetHeight);
        }

        foreach ($imagick as $image) {
            $image->cropImage($targetWidth, $targetHeight, $x, $y);
        }
    }
}

