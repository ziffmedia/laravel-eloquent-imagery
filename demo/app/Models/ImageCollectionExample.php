<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery;

class ImageCollectionExample extends Model
{
    use HasEloquentImagery;

    protected $eloquentImagery = [
        'images' => [
            'path'       => 'image-collection-examples/{id}/{index}.{extension}',
            'collection' => true,
        ],
    ];
}
