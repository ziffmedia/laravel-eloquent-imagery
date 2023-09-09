<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use WeakMap;

class CastInstanceManager
{
    protected array $observedModelClasses = [];
    protected array $defaultPathTemplates = [];
    protected Filesystem $defaultFilesystem;
    protected WeakMap $modelFilesystems;
    protected WeakMap $models;
    protected WeakMap $replicatingModels;

    public function __construct()
    {
        $this->defaultFilesystem = app(FilesystemManager::class)->disk(config('eloquent-imagery.filesystem', config('filesystems.default')));
        $this->modelFilesystems = new WeakMap;
        $this->models = new WeakMap;
        $this->replicatingModels = new WeakMap;
    }

    public function has(Model $model, string $attribute): bool
    {
        return isset($this->models[$model][$attribute]);
    }

    public function get(Model $model, string $attribute, bool $asCollection = false)
    {
        $modelClass = get_class($model);

        $defaultPathTemplate = $this->defaultPathTemplates[$modelClass . ':' . $attribute]
            ?? $this->generateDefaultPathTemplate($model, $attribute, $asCollection);

        if (isset($this->models[$model][$attribute])) {
            $image = $this->models[$model][$attribute];

            $this->prepareImageWithFilesystem($model, $attribute);

            return $image;
        }

        if (!isset($this->models[$model])) {
            $this->models[$model] = [];
        }

        $image = new Image();

        // set the default path template for this image
        $image->setPathTemplate($defaultPathTemplate);

        if ($asCollection) {
            $imageCollection = new ImageCollection($image);
        }

        $this->models[$model][$attribute] = $imageCollection ?? $image;

        $this->prepareImageWithFilesystem($image, $model, $attribute);

        return $this->models[$model][$attribute];
    }

    public function registerFilesystem(Filesystem $filesystem, Model|string $forModel, string $forAttribute = null): void
    {
        $forAttribute ??= 'all';

        $this->modelFilesystems[$forModel] = [$forAttribute => $filesystem];
    }

    public function unregisterFilesystem(Model|string $model, string $forAttribute = null): void
    {
        if ($forAttribute === null) {
            unset($this->modelFilesystems[$model]);

            return;
        }

        unset($this->modelFilesystems[$model][$forAttribute]);
    }

    protected function prepareImageWithFilesystem(Image|ImageCollection $image, Model $model, $attribute): void
    {
        // @todo use $model and $attribute to decide which filesystem needs to be injected

        $image->setFilesystem($this->defaultFilesystem);
    }

    protected function generateDefaultPathTemplate(Model $model, string $attribute, bool $forCollection): string
    {
        $modelName = class_basename($model);

        $defaultPathTemplate = $forCollection
            ? Str::kebab(Str::pluralStudly(class_basename($modelName)))
                . '/{'
                . Str::kebab($model->getKeyName())
                . '}/'
                . Str::kebab(str_replace('_', ' ', $attribute))
                . '-{index}'
                . '.{extension}'
            : Str::kebab(Str::pluralStudly(class_basename($modelName)))
                . '/{'
                . Str::kebab($model->getKeyName())
                . '}-'
                . Str::kebab(str_replace('_', ' ', $attribute))
                . '.{extension}';

        $this->defaultPathTemplates[get_class($model) . ':' . $attribute] = $defaultPathTemplate;

        return $defaultPathTemplate;
    }

    public function booting($event, $payload): void
    {
        $model = $payload[0];

        // get model from event name
        $modelClass = get_class($model);

        if (isset($this->observedModelClasses[$modelClass])) {
            return;
        }

        if (Arr::first($model->getCasts(), fn ($castSpec) => Str::startsWith($castSpec, 'ZiffMedia\\LaravelEloquentImagery'), false) === false) {
            $this->observedModelClasses[$modelClass] = false;

            return;
        }

        /** @var Model $modelClass */
        $dispatcher = Model::getEventDispatcher();

        $dispatcher->listen("eloquent.saving: {$modelClass}", $this->saving(...));
        $dispatcher->listen("eloquent.saved: {$modelClass}", $this->saved(...));
        $dispatcher->listen("eloquent.deleting: {$modelClass}", $this->deleting(...));
        $dispatcher->listen("eloquent.deleted: {$modelClass}", $this->deleted(...));
        $dispatcher->listen("eloquent.replicating: {$modelClass}", $this->replicating(...));

        $this->observedModelClasses[$modelClass] = true;
    }

    public function saving(Model $model): void
    {
        if (isset($this->replicatingModels[$model])) {
            foreach ($model->getCasts() as $attribute => $castSpec) {
                if (! Str::startsWith($castSpec, 'ZiffMedia\\LaravelEloquentImagery')) {
                    continue;
                }

                // @todo get already hydrated instance, or get it from caster

                $value = $model->{$attribute};
                $value->resetToFreshState();
            }
        }

        if (! isset($this->models[$model])) {
            return;
        }

        foreach ($this->models[$model] as $image) {
            if ($image->pathHasReplacements()) {
                // @todo filter scalars here
                $modelAttributes = (fn () => $this->attributes)->bindTo($model, $model)();

                $image->updatePath($modelAttributes);
            }

            if ($image instanceof ImageCollection) {
                $image->purgeRemovedImages();
            }
        }
    }

    public function saved(Model $model): void
    {
        if (! isset($this->models[$model])) {
            return;
        }

        $errors = [];

        foreach ($this->models[$model] as $attribute => $image) {
            if ($image->pathHasReplacements()) {
                $modelAttributes = (fn () => $this->attributes)->bindTo($model, $model)();

                $image->updatePath($modelAttributes);

                if ($image->pathHasReplacements()) {
                    $errors[] = "After saving row, image for attribute {$attribute}'s path still contains unresolvable path replacements";
                }

                $imageState = $image->getStateAsAttributeData();

                $keyName = $model->getKeyName();

                $model->getConnection()
                    ->table($model->getTable())
                    ->where($keyName, $modelAttributes[$keyName])
                    ->update([$attribute => json_encode($imageState)]);
            }

            $image->flush();
        }

        if ($errors) {
            throw new RuntimeException(implode('; ', $errors));
        }
    }

    public function deleting(Model $model): void
    {
        // we need to get the tracked attributes so we can later remove them from disk
        foreach ($model->getCasts() as $attribute => $castSpec) {
            if (! Str::startsWith($castSpec, 'ZiffMedia\\LaravelEloquentImagery')) {
                continue;
            }

            $value = $model->{$attribute};
        }
    }

    public function deleted(Model $model): void
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && ! $model->isForceDeleting()) {
            return;
        }

        foreach ($this->models[$model] as $key => $image) {
            if ($image->exists()) {
                $image->remove();
                $image->flush();
            }
        }
    }

    public function replicating(Model $model): void
    {
        // $this->replicatingModels[spl_object_id($model)] = $model;
        $this->replicatingModels[$model] = true;
    }
}
