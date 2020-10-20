<?php

namespace ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations;

use Illuminate\Support\Collection;
use Imagick;
use RuntimeException;

class Fit implements ImagickTransformationInterface
{
    public function applyImagick(Collection $arguments, Imagick $imagick)
    {
        if (!$arguments->has('fit')) {
            return;
        }

        $fit = $arguments->get('fit');
        $width = (int) $arguments->get('width', 0);
        $height = (int) $arguments->get('height', 0);

        if ($width === 0 && $height === 0) {
            return;
        }

        [$originalWidth, $originalHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

        switch ($fit) {

            case 'resize':
                foreach ($imagick as $image) {
                    $image->resizeImage(
                        $width !== 0 ? $width : $originalWidth,
                        $height !== 0 ? $height : $originalHeight,
                        Imagick::FILTER_POINT,
                        1
                    );
                }

                break;

            case 'scale':
                foreach ($imagick as $image) {
                    $image->scaleImage(
                        $width !== 0 ? $width : 1920,
                        $height !== 0 ? $height : 1920,
                        true
                    );
                }

                break;

            case 'limit':

                $limitWidth = $width !== 0 ? $width : $originalWidth;
                $limitHeight = $height !== 0 ? $height : $originalHeight;

                // this only needs to be applied to images that exceed the limits on either width or height
                if ($originalWidth <= $limitWidth && $originalHeight <= $limitHeight) {
                    break;
                }

                foreach ($imagick as $image) {
                    $image->scaleImage(
                        $limitWidth,
                        $limitHeight,
                        true
                    );
                }

                break;

            case 'lpad':
                [$originalWidth, $originalHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

                $width = $width !== 0 ? $width : $originalWidth;
                $height = $height !== 0 ? $height : $originalHeight;
                $background = $arguments->get('background');

                foreach ($imagick as $image) {

                    if ($background) {
                        $image->setImageBackgroundColor($background);
                    }

                    if ($width > $originalWidth && $height > $originalHeight) {
                        $image->extentImage(
                            $width,
                            $height,
                            ($originalWidth - $width ) / 2,
                            ($originalHeight - $height) / 2
                        );
                    } else {
                        $image->thumbnailImage(
                            $width !== 0 ? $width : 1920,
                            $height !== 0 ? $height : 1920,
                            true,
                            true
                        );
                    }
                }

                break;
        }
    }
}
