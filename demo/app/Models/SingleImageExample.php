<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ZiffMedia\LaravelEloquentImagery\Eloquent\EloquentImage;
use ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery;

class SingleImageExample extends Model
{
    use HasEloquentImagery;

    protected $casts = [
        'variations' => 'json',
        'image' => EloquentImage::class . ':single-image-examples/{id}.{extension}'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->name) {
                $model->name = 'Test Image ' . rand(1000, 9999);
            }
        });
    }

    // protected $eloquentImagery = [
    //     'image' => [
    //         'path'    => 'single-image-examples/{id}.{extension}',
    //         'presets' => [
    //             'thumbnail'   => 'fit_resize|size_50x50|v',
    //             'timestamped' => 'v', // ?
    //         ],
    //     ],
    // ];
}
