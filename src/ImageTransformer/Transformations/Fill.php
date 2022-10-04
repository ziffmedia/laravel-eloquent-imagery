<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class Fill implements ImagickTransformationInterface
{
    use Concerns\HasGravityFeatures;

    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (! $arguments->has('fill')) {
            return;
        }

        [$imgWidth, $imgHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        $targetWidth = (int) $arguments->get('width');
        $targetHeight = (int) $arguments->get('height');

        if ($targetWidth === 0 && $targetHeight === 0) {
            return;
        }

        if ($targetWidth === 0) {
            $targetWidth = $imgWidth;
        }

        if ($targetHeight === 0) {
            $targetHeight = $imgHeight;
        }

        $gravity = $this->getGravityParam($arguments);

        if ($imgWidth < $targetWidth || $imgHeight < $targetHeight) {
            // img needs to be scaled up so it covers target area.
            $scaleUpFactor = ($targetWidth - $imgWidth > $targetHeight - $imgHeight)
                ? $targetWidth / $imgWidth
                : $targetHeight / $imgHeight;

            // new width and height
            $imgWidth = (int) ($imgWidth * $scaleUpFactor);
            $imgHeight = (int) ($imgHeight * $scaleUpFactor);

            foreach ($imagick as $image) {
                $image->scaleImage(
                    $imgWidth,
                    $imgHeight,
                    true
                );
            }
        }

        // cropping only one side to get target proportion
        $this->gravityCrop($imagick, $gravity, $imgWidth, $imgHeight, $targetWidth, $targetHeight);

        // now resize to achieve target size
        foreach ($imagick as $image) {
            $image->scaleImage(
                $targetWidth !== 0 ? $targetWidth : $imgWidth,
                $targetHeight !== 0 ? $targetHeight : $imgHeight,
                true
            );
        }
    }

    private function gravityCrop(Imagick $imagick, $gravity, int $imgWidth, int $imgHeight, int $targetWidth, int $targetHeight)
    {
        $originProportion = $imgWidth / $imgHeight;
        $targetProportion = $targetWidth / $targetHeight;

        $newImgWidth = $imgWidth;
        $newImgHeight = $imgHeight;

        if ($originProportion < $targetProportion) {
            // means we need reduce height
            $newImgHeight = $imgWidth * $targetHeight / $targetWidth;
            $x = 0;
            $y = $this->getGravityYValue($gravity, $imgHeight, $newImgHeight);
        } else {
            // need reduce width
            $newImgWidth = $imgHeight * $targetWidth / $targetHeight;
            $y = 0;
            $x = $this->getGravityXValue($gravity, $imgWidth, $newImgWidth);
        }

        foreach ($imagick as $image) {
            $image->cropImage($newImgWidth, $newImgHeight, $x, $y);
        }
    }
}
