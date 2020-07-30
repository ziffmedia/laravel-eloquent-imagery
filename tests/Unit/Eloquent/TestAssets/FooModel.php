<?php

namespace ZiffMedia\LaravelEloquentImagery\Test\Unit\Eloquent\TestAssets;

use Illuminate\Database\Eloquent\Model;
use ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;

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
