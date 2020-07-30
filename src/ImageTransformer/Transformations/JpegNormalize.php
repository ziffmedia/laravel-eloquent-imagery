<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class JpegNormalize implements ImagickTransformationInterface
{
    protected $fixColorspace = true;

    protected $samplingFactors = [];

    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if ($this->fixColorspace) {
            if ($imagick->getColorspace() === Imagick::COLORSPACE_CMYK) {
                $imagick->setColorspace(Imagick::COLORSPACE_RGB);
            }
        }

        if ($this->samplingFactors) {
            $imagick->setSamplingFactors($this->samplingFactors); // ['2x2', '1x1', '1x1']
        }
    }
}
