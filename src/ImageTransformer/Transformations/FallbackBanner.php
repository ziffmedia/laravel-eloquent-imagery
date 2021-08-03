<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;
use ImagickDraw;

class FallbackBanner implements ImagickTransformationInterface
{

    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('fit') || $arguments->get('fallback') == false) {
            return;
        }

        [$originalWidth, $originalHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        foreach ($imagick as $image) {
            $draw = new ImagickDraw();
            $draw->setStrokeWidth(1);
            $draw->line(1, 1, $originalWidth - 1, $originalHeight - 1);
            $draw->line(1, $originalHeight-1, $originalWidth-1, 1);

            $image->drawImage($draw);
        }
    }
}

