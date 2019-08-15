<?php

namespace ZiffMedia\Laravel\EloquentImagery\Test\Unit\Eloquent\TestAssets;

use Illuminate\Database\Eloquent\Model;
use ZiffMedia\Laravel\EloquentImagery\Eloquent\HasEloquentImagery;
use ZiffMedia\Laravel\EloquentImagery\Eloquent\Image;

/**
 * @property Image $image
 */
class FooModel extends Model
{
    use HasEloquentImagery;

    protected $eloquentImagery = [
        'image' => 'images/{id}.{extension}'
    ];
}
