<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ReflectionProperty;
use RuntimeException;

class EloquentImageryObserver
{
    /** @var ReflectionProperty  */
    protected $eloquentImageryImagesReflector;

    /** @var ReflectionProperty */
    protected $attributesReflector;

    /**
     * EloquentImageryObserver constructor.
     * @param $modelClassToObserve
     * @throws \ReflectionException
     */
    public function __construct($modelClassToObserve)
    {
        $this->eloquentImageryImagesReflector = new ReflectionProperty($modelClassToObserve, 'eloquentImageryImages');
        $this->eloquentImageryImagesReflector->setAccessible(true);

        $this->attributesReflector = new ReflectionProperty($modelClassToObserve, 'attributes');
        $this->attributesReflector->setAccessible(true);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function retrieved(Model $model)
    {
        /** @var Image[]|ImageCollection[] $eloquentImageryImages */
        $eloquentImageryImages = $this->eloquentImageryImagesReflector->getValue($model);

        $modelAttributes = $this->attributesReflector->getValue($model);

        foreach ($eloquentImageryImages as $attribute => $image) {
            // in the case a model was retrieved and the image column was not returned
            if (!array_key_exists($attribute, $modelAttributes)) {
                continue;
            }

            $attributeData = $modelAttributes[$attribute];
            $modelAttributes[$attribute] = $image;

            if ($attributeData == '') {
                continue;
            }

            if (is_string($attributeData)) {
                $attributeData = json_decode($attributeData, true);
            }

            $image->setStateFromAttributeData($attributeData);
        }

        $this->attributesReflector->setValue($model, $modelAttributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function saving(Model $model)
    {
        /** @var Image[]|ImageCollection[] $eloquentImageryImages */
        $eloquentImageryImages = $this->eloquentImageryImagesReflector->getValue($model);

        $casts = $model->getCasts();

        $modelAttributes = $this->attributesReflector->getValue($model);

        foreach ($eloquentImageryImages as $attribute => $image) {
            if ($image->pathHasReplacements()) {
                $image->updatePath([], $model);
            }

            if ($image instanceof ImageCollection) {
                $image->purgeRemovedImages();
            } elseif ($image instanceof Image && !$image->exists()) {
                $modelAttributes[$attribute] = null;
                continue;
            }

            $attributeData = $image->getStateAsAttributeData();

            $value = (isset($casts[$attribute]) && $casts[$attribute] === 'json')
                ? $attributeData
                : json_encode($attributeData);

            $modelAttributes[$attribute] = $value;
        }

        $this->attributesReflector->setValue($model, $modelAttributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function saved(Model $model)
    {
        /** @var Image[]|ImageCollection[] $eloquentImageryImages */
        $eloquentImageryImages = $this->eloquentImageryImagesReflector->getValue($model);

        $casts = $model->getCasts();

        $errors = [];

        $modelAttributes = $this->attributesReflector->getValue($model);

        foreach ($eloquentImageryImages as $attribute => $image) {
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

            $modelAttributes[$attribute] = $image;
        }

        $this->attributesReflector->setValue($model, $modelAttributes);

        if ($errors) {
            throw new RuntimeException(implode('; ', $errors));
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffMedia\LaravelEloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function deleted(Model $model)
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && !$model->isForceDeleting()) {
            return;
        }

        /** @var Image[]|ImageCollection[] $eloquentImageryImages */
        $eloquentImageryImages = $this->eloquentImageryImagesReflector->getValue($model);

        foreach ($eloquentImageryImages as $image) {
            if ($image->exists()) {
                $image->remove();
                $image->flush();
            }
        }
    }
}
