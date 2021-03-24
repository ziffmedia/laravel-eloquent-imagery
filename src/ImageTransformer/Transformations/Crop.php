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
        $targetWidth = (int)$arguments->get('width', 0);
        $targetHeight = (int)$arguments->get('height', 0);

        [$imgWidth, $imgHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        //cropping only one side to get target proportion
        if ($this->oneSideCrop($imagick, $crop, $imgWidth, $imgHeight, $targetWidth, $targetHeight)) {
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
    }

    private function oneSideCrop(Imagick $imagick, $crop, $imgWidth, $imgHeight, $targetWidth, $targetHeight)
    {
        $originProportion = $imgWidth / $imgHeight;
        $targetProportion = $targetWidth / $targetHeight;

        $newImgWidth = $imgWidth;
        $newImgHeight = $imgHeight;

        $x = 0;
        $y = 0;

        if ($originProportion < $targetProportion) {
            //means we need reduce height
            $newImgHeight = (int)($imgWidth * $targetHeight / $targetWidth);
            //in this case we do not change width, thus x always 0
            switch ($crop) {
                case "topCenter":
                    $y = 0;
                    break;
                case "center":
                case "centerRight":
                case "centerLeft":
                    if ($imgHeight > $newImgHeight)
                        $y = (int)(($imgHeight - $newImgHeight) / 2);
                    break;
                case "bottomCenter":
                    if ($imgHeight > $newImgHeight)
                        $y = $imgHeight - $newImgHeight;
                    break;
                default:
                    return false;
            }

        } else {
            //need reduce width
            $newImgWidth = (int)($imgHeight * $targetWidth / $targetHeight);
            //in this case we do not change height, thus y always 0
            switch ($crop) {
                case "topCenter":
                case "bottomCenter":
                case "center":
                    if ($imgWidth > $newImgWidth)
                        $x = (int)(($imgWidth - $newImgWidth) / 2);
                    break;
                case "centerRight":
                    if ($imgWidth > $newImgWidth)
                        $x = $imgWidth - $newImgWidth;
                    break;
                case "centerLeft":
                    $x = 0;
                    break;
                default:
                    return false;
            }
        }

        foreach ($imagick as $image) {
            $image->cropImage($newImgWidth, $newImgHeight, $x, $y);
        }
        return true;
    }
}

