<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class EloquentImageryObserver
{
    /** @var array<array<Image|ImageCollection>> */
    protected static array $trackedModelImages = [];

    public static function trackModelImage(Model $model, string $attribute, Image|ImageCollection $image): void
    {
        $modelId = spl_object_id($model);

        if (!isset(static::$trackedModelImages[$modelId])) {
            static::$trackedModelImages[$modelId] = [];
        }

        static::$trackedModelImages[$modelId][$attribute] = $image;
    }

    public function saving(Model $model): void
    {
        $modelId = spl_object_id($model);

        if (!isset(static::$trackedModelImages[$modelId])) {
            return;
        }

        foreach (static::$trackedModelImages[$modelId] as $attribute => $image) {
            if ($image->pathHasReplacements()) {
                $image->updatePath([], $model);
            }

            if ($image instanceof ImageCollection) {
                $image->purgeRemovedImages();
            }
        }
    }

    public function saved(Model $model): void
    {
        $modelId = spl_object_id($model);

        if (!isset(static::$trackedModelImages[$modelId])) {
            return;
        }

        $errors = [];

        foreach (static::$trackedModelImages[$modelId] as $attribute => $image) {
            if ($image->pathHasReplacements()) {
                $image->updatePath([], $model);

                if ($image->pathHasReplacements()) {
                    $errors[] = "After saving row, image for attribute {$attribute}'s path still contains unresolvable path replacements";
                }

                $imageState = $image->getStateAsAttributeData();

                $value = json_encode($imageState);

                $model->getConnection()
                    ->table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->update([$attribute => $value]);
            }

            $image->flush();
        }

        if ($errors) {
            throw new RuntimeException(implode('; ', $errors));
        }

        unset(static::$trackedModelImages[$modelId]);
    }

    public function deleted(Model $model): void
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && ! $model->isForceDeleting()) {
            return;
        }

        $modelId = spl_object_id($model);

        if (!isset(static::$trackedModelImages[$modelId])) {
            return;
        }

        foreach (static::$trackedModelImages[$modelId] as $attribute => $image) {
            if ($image->exists()) {
                $image->remove();
                $image->flush();
            }
        }
    }
}
