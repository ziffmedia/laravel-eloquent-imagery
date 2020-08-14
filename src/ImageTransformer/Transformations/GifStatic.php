<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class GifStatic implements ImagickTransformationInterface
{
    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('static')) {
            return;
        }

        if (!$imagick->getImageFormat() === 'GIF' || $imagick->getNumberImages() < 2) {
            return;
        }

        $frames = $imagick->getNumberImages();

        // figure out which frame to keep
        $keep = $arguments->get('static');
        $keep = $keep === true ? 0 : (int) $keep;

        if ($keep > $frames) {
            $keep = ($frames - 1);
        }

        $keptFramesIndex = 0;

        for ($index = 0; $index < $frames; $index++) {
            if ($index === $keep) {
                $keptFramesIndex++;
                continue;
            }

            $imagick->setIteratorIndex($keptFramesIndex);
            $imagick->removeImage();
        }
    }
}

