<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use RuntimeException;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasEloquentImagery
{
    /** @var Image[]|ImageCollection[] */
    protected static $eloquentImageryPrototypes = [];

    /** @var Image[]|ImageCollection[] */
    protected $eloquentImageryImages = [];

    public static function bootHasEloquentImagery()
    {
        $observer = new EloquentImageryObserver(get_called_class());

        // register directly so that the instance is preserved (not preserved via static::observe())
        static::registerModelEvent('retrieved', [$observer, 'retrieved']);
        static::registerModelEvent('saving', [$observer, 'saving']);
        static::registerModelEvent('saved', [$observer, 'saved']);
        static::registerModelEvent('deleted', [$observer, 'deleted']);
    }

    public function initializeHasEloquentImagery()
    {
        if (!empty($this->eloquentImageryImages)) {
            throw new RuntimeException('$eloquentImageryImages should be empty, are you sure you have your configuration in the right place?');
        }

        if (empty($this->eloquentImagery) || !property_exists($this, 'eloquentImagery')) {
            throw new RuntimeException('You are using ' . __TRAIT__ . ' but have not yet configured it through $eloquentImagery, please see the docs');
        }

        foreach ($this->eloquentImagery as $attribute => $config) {
            if (is_string($config)) {
                $config = ['path' => $config];
            }

            if (!is_array($config)) {
                throw new RuntimeException('configuration must be a string or array');
            }

            if (!isset(static::$eloquentImageryPrototypes[$attribute])) {
                $prototype = app(Image::class, [
                    'pathTemplate' => $config['path'],
                    'presets' => $config['presets'] ?? []
                ]);

                if (isset($config['collection']) && $config['collection'] === true) {
                    $prototype = app(ImageCollection::class, ['imagePrototype' => $prototype]);
                }

                static::$eloquentImageryPrototypes[$attribute] = $prototype;
            } else {
                $prototype = static::$eloquentImageryPrototypes[$attribute];
            }

            $this->attributes[$attribute] = $this->eloquentImageryImages[$attribute] = clone $prototype;
        }
    }
}
