<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class Crop implements ImagickTransformationInterface
{
    use Concerns\HasGravityFeatures;

    /**
     * @param Collection $arguments
     * @param Imagick $imagick
     */
    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('crop') || $arguments->has('fill')) {
            return;
        }

        $crop = $arguments->get('crop');

        // crop command must be in the format \dx\d
        if (!preg_match('#(?P<x>\d+){0,1}x(?P<y>\d+){0,1}#', $crop, $matches)) {
            return;
        }

        $gravity = $this->getGravityParam($arguments);

        [$imgWidth, $imgHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        $targetWidth = isset($matches['x']) && $matches['x'] > 0 ? (int) $matches['x'] : $imgWidth;
        $targetHeight = isset($matches['y']) && $matches['y'] > 0 ? (int) $matches['y'] : $imgHeight;

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

