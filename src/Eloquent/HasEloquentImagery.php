<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        $modelClass = get_called_class();

        // register directly so that the instance is preserved (not preserved via static::observe())
        static::registerModelEvent('retrieved', [$modelClass, 'eloquentImageryRetrieved']);
        static::registerModelEvent('creating', [$modelClass, 'eloquentImagerySerializing']);
        static::registerModelEvent('created', [$modelClass, 'eloquentImageryUnserializing']);
        static::registerModelEvent('updating', [$modelClass, 'eloquentImagerySerializing']);
        static::registerModelEvent('updated', [$modelClass, 'eloquentImageryUnserializing']);
        static::registerModelEvent('deleted', [$modelClass, 'eloquentImageryDeleted']);
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


    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public static function eloquentImageryRetrieved(Model $model)
    {
        foreach ($model->eloquentImageryImages as $attribute => $image) {
            // in the case a model was retrieved and the image column was not returned
            if (!array_key_exists($attribute, $model->attributes)) {
                continue;
            }

            $attributeData = $model->attributes[$attribute];
            $model->attributes[$attribute] = $image;

            if ($attributeData == '') {
                continue;
            }

            if (is_string($attributeData)) {
                $attributeData = json_decode($attributeData, true);
            }

            $image->setStateFromAttributeData($attributeData);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public static function eloquentImagerySerializing(Model $model)
    {
        $casts = $model->getCasts();

        foreach ($model->eloquentImageryImages as $attribute => $image) {
            if ($image->pathHasReplacements()) {
                $image->updatePath([], $model);
            }

            if ($image instanceof ImageCollection) {
                $image->purgeRemovedImages();
            } elseif ($image instanceof Image && !$image->exists()) {
                $model->attributes[$attribute] = null;
                continue;
            }

            $attributeData = $image->getStateAsAttributeData();

            $value = (isset($casts[$attribute]) && $casts[$attribute] === 'json')
                ? $attributeData
                : json_encode($attributeData);

            $model->attributes[$attribute] = $value;
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public static function eloquentImageryUnserializing(Model $model)
    {
        $casts = $model->getCasts();

        $errors = [];

        foreach ($model->eloquentImageryImages as $attribute => $image) {
            if ($image->pathHasReplacements()) {

                $image->updatePath([], $model);

                if ($image->pathHasReplacements()) {
                    $errors[] = "After saving row, image for attribute {$attribute}'s path still contains unresolvable path replacements";
                }

                $imageState = $image->getStateAsAttributeData();

                $value = (isset($casts[$attribute]) && $casts[$attribute] === 'json')
                    ? $imageState
                    : json_encode($imageState);

                $model->getConnection()
                    ->table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->update([$attribute => $value]);
            }
            $image->flush();

            $model->attributes[$attribute] = $image;
        }

        if ($errors) {
            throw new RuntimeException(implode('; ', $errors));
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public static function eloquentImageryDeleted(Model $model)
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && !$model->isForceDeleting()) {
            return;
        }

        foreach ($model->eloquentImageryImages as $image) {
            if ($image->exists()) {
                $image->remove();
                $image->flush();
            }
        }
    }
}
