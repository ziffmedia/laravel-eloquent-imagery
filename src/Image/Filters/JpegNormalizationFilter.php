<?php

namespace ZiffMedia\Laravel\EloquentImagery\Image\Filters;

use Imagick;
use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class JpegNormalizationFilter implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        $core = $image->getCore();

        if ($core instanceof Imagick) {
            $core->stripImage();

            if ($core->getImageColorspace() == Imagick::COLORSPACE_CMYK) {
                $core->transformImageColorspace(Imagick::COLORSPACE_RGB);
            }
        }

        return $image;
    }
}
