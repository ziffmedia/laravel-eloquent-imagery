<?php

namespace ZiffMedia\Laravel\EloquentImagery\Image;

use Intervention\Image\AbstractFont;
use Intervention\Image\ImageManager;

class PlaceholderImageFactory
{
    public function create($width, $height, $backgroundColor = '#AAAAAA')
    {
        $image = (new ImageManager(['driver' => 'imagick']))->canvas($width, $height, $backgroundColor);

        $image->text("{$width}x{$height}", $width / 2, $height / 2, function(AbstractFont $font) use ($width) {
            $font->align('center');
            $font->valign('middle');
            $font->color('000000');
            $font->file(__DIR__ . '/../../fonts/OverpassMono-Regular.ttf');
            $font->size(ceil((.9 * $width) / 7));
        });

        return $image->encode('png')->__toString();
    }
}