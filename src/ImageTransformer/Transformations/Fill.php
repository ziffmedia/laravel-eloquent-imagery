<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class Fill implements ImagickTransformationInterface
{
    use Concerns\HasGravityFeatures;

    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('fill')) {
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
            $increaseC = ($targetWidth - $imgWidth > $targetHeight - $imgHeight)
                ? $targetWidth / $imgWidth
                : $targetHeight / $imgHeight;

            //we need to save new image size into variables because they are being used later on.
            $imgWidth = (int)($imgWidth * $increaseC);
            $imgHeight = (int)($imgHeight * $increaseC);

            foreach ($imagick as $image) {
                $image->resizeImage(
                    $imgWidth,
                    $imgHeight,
                    Imagick::FILTER_POINT,
                    1
                );
            }
        }

        // cropping only one side to get target proportion
        $this->gravityCrop($imagick, $gravity, $imgWidth, $imgHeight, $targetWidth, $targetHeight);

        // now resize to achieve target size
        foreach ($imagick as $image) {
            $image->resizeImage(
                $targetWidth !== 0 ? $targetWidth : $imgWidth,
                $targetHeight !== 0 ? $targetHeight : $imgHeight,
                Imagick::FILTER_POINT,
                1
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
