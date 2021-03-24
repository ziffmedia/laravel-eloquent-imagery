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
        $width = (int)$arguments->get('width', 0);
        $height = (int)$arguments->get('height', 0);

        [$imgWidth, $imgHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        $x = 0;
        $y = 0;

        switch ($crop) {
            case "topCenter":
                if ($imgWidth > $width)
                    $x = (int)(($imgWidth - $width) / 2);
                break;
            case "center":
                //means x-center y-center
                if ($imgWidth > $width)
                    $x = (int)(($imgWidth - $width) / 2);
                if ($imgHeight > $height)
                    $y = (int)(($imgHeight - $height) / 2);
                break;
            case "bottomCenter":
                if ($imgWidth > $width)
                    $x = (int)(($imgWidth - $width) / 2);
                if ($imgHeight > $height)
                    $y = $imgHeight - $height;
                break;
        }
        foreach ($imagick as $image) {
            $image->cropImage($width, $height, $x, $y);
        }
    }
}

