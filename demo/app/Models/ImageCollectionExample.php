<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ZiffMedia\LaravelEloquentImagery\Eloquent\EloquentImageCast;
use ZiffMedia\LaravelEloquentImagery\Eloquent\EloquentImageCollectionCast;
use ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery;

class ImageCollectionExample extends Model
{
    use HasEloquentImagery;

    protected $casts = [
        'images' => EloquentImageCollectionCast::class . ':image-collection-examples/{id}/{index}.{extension}'
    ];
}
