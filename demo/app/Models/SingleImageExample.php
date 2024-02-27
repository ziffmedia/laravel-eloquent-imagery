<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ZiffMedia\LaravelEloquentImagery\Eloquent\EloquentImageCast;

class SingleImageExample extends Model
{
    protected $casts = [
        'variations' => 'json',
        'image' => EloquentImageCast::class . ':single-image-examples/{id}.{extension}'
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
}
