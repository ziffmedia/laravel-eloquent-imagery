<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use ReflectionException;
use RuntimeException;

trait HasEloquentImagery
{
    public static function bootHasEloquentImagery(): void
    {
        try {
            $observer = new EloquentImageryObserver(get_called_class());
        } catch (ReflectionException $e) {
            throw new RuntimeException('HasEloquentImagery appears to have been applied to a class that is not a Model');
        }

        // register directly so that the instance is preserved (not preserved via static::observe())
        static::registerModelEvent('saving', [$observer, 'saving']);
        static::registerModelEvent('saved', [$observer, 'saved']);
        static::registerModelEvent('deleted', [$observer, 'deleted']);
        static::registerModelEvent('replicating', [$observer, 'replicating']);
    }
}
