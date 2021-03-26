<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;

class Fill extends BaseTransformation
{

    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('fill')) {
            return;
        }

        $targetWidth = (int)$arguments->get('width', 0);
        $targetHeight = (int)$arguments->get('height', 0);
        $gravity = $this->getGravityParam($arguments);

        [$imgWidth, $imgHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        if ($imgWidth < $targetWidth || $imgHeight < $targetHeight) {
            //img needs to be scaled up so it covers target area.
            $originProportion = $imgWidth / $imgHeight;
            if ($targetWidth - $imgWidth > $targetHeight - $imgHeight)
                $increaseC = $targetWidth / $imgWidth;
            else
                $increaseC = $targetHeight / $imgHeight;

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


        //cropping only one side to get target proportion
        $this->gravityCrop($imagick, $gravity, $imgWidth, $imgHeight, $targetWidth, $targetHeight);

        //now resize to achieve target size
        foreach ($imagick as $image) {
            $image->resizeImage(
                $targetWidth !== 0 ? $targetWidth : $imgWidth,
                $targetHeight !== 0 ? $targetHeight : $imgHeight,
                Imagick::FILTER_POINT,
                1
            );
        }

    }

    private function gravityCrop(Imagick $imagick, $gravity, $imgWidth, $imgHeight, $targetWidth, $targetHeight)
    {
        $originProportion = $imgWidth / $imgHeight;
        $targetProportion = $targetWidth / $targetHeight;

        $newImgWidth = $imgWidth;
        $newImgHeight = $imgHeight;

        if ($originProportion < $targetProportion) {
            //means we need reduce height
            $newImgHeight = (int)($imgWidth * $targetHeight / $targetWidth);
            $x = 0;
            $y = $this->getGravityYValue($gravity, $imgHeight, $newImgHeight);
        } else {
            //need reduce width
            $newImgWidth = (int)($imgHeight * $targetWidth / $targetHeight);
            $y = 0;
            $x = $this->getGravityXValue($gravity, $imgWidth, $newImgWidth);
        }

        foreach ($imagick as $image) {
            $image->cropImage($newImgWidth, $newImgHeight, $x, $y);
        }
    }
}