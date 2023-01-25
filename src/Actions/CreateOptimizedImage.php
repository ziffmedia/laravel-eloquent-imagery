<?php

namespace ZiffMedia\LaravelEloquentImagery\Actions;

use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;

class CreateOptimizedImage
{
    public function __invoke(Image $image)
    {
        if ($image->canBeOptimized()) {
            $image->optimize();
        }
    }
}
