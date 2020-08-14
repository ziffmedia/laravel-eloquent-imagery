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
            throw new RuntimeException('Both width and height cannot be 0 for fit operations');
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
                foreach ($imagick as $image) {
                    $image->scaleImage(
                        min($originalWidth, $width ?? $originalWidth),
                        min($originalHeight, $height ?? $originalHeight),
                        false
                    );
                }

                break;

            case 'lpad':
                [$originalWidth, $originalHeight] = [$imagick->getImageWidth(), $imagick->getImageHeight()];

                $width = $width !== 0 ? $width : $originalWidth;
                $height = $height !== 0 ? $height : $originalHeight;

                foreach ($imagick as $image) {

                    $image->setImageBackgroundColor(
                        $arguments->get('background', 'black')
                    );

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
