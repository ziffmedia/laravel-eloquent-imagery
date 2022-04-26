<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Illuminate\Database\Eloquent\Model;
use ReflectionException;
use RuntimeException;

/**
 * @mixin Model
 * @property array $eloquentImagery
 */
trait HasEloquentImagery
{
    /** @var Image[]|ImageCollection[] */
    protected static array $eloquentImageryRuntime = ['configs' => [], 'prototypes' => []];

    /** @var Image[]|ImageCollection[] */
    protected array $eloquentImageryImages = [];

    public static function bootHasEloquentImagery(): void
    {
        try {
            $observer = new EloquentImageryObserver(get_called_class());
        } catch (ReflectionException $e) {
            throw new RuntimeException('HasEloquentImagery appears to have been applied to a class that is not a Model');
        }

        // register directly so that the instance is preserved (not preserved via static::observe())
        static::registerModelEvent('retrieved', [$observer, 'retrieved']);
        static::registerModelEvent('saving', [$observer, 'saving']);
        static::registerModelEvent('saved', [$observer, 'saved']);
        static::registerModelEvent('deleted', [$observer, 'deleted']);
    }

    public function initializeHasEloquentImagery(): void
    {
        if (!empty($this->eloquentImageryImages)) {
            throw new RuntimeException('$eloquentImageryImages should be empty, are you sure you have your configuration in the right place?');
        }

        if (empty($this->eloquentImagery) || !property_exists($this, 'eloquentImagery')) {
            throw new RuntimeException('You are using ' . __TRAIT__ . ' but have not yet configured it through $eloquentImagery, please see the docs');
        }

        foreach ($this->eloquentImagery as $attribute => $config) {
            if (!isset(static::$eloquentImageryRuntime['configs'][$attribute])) {
                if (is_string($config)) {
                    $config = ['path' => $config];
                }

                if (!is_array($config)) {
                    throw new RuntimeException('configuration must be a string or array');
                }

                $config = [
                    'pathTemplate' => $config['path'],
                    'presets'      => $config['presets'] ?? [],
                    'collection'   => $config['collection'] ?? false,
                    'attributes'   => $config['attributes'] ?? []
                ];

                static::$eloquentImageryRuntime['configs'][$attribute] = $config;

                $prototype = app(Image::class, [
                    'pathTemplate' => $config['pathTemplate'],
                    'presets'      => $config['presets']
                ]);

                if (isset($config['collection']) && $config['collection'] === true) {
                    $prototype = app(ImageCollection::class, ['imagePrototype' => $prototype]);
                }

                static::$eloquentImageryRuntime['prototypes'][$attribute] = $prototype;
            }

            $this->attributes[$attribute] = $this->eloquentImageryImages[$attribute] = clone static::$eloquentImageryRuntime['prototypes'][$attribute];
        }
    }
}
