<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EloquentImageCollectionCast implements CastsAttributes
{
    protected static CastInstanceManager $castInstanceManager;

    public function __construct(
        protected ?string $pathTemplate = null,
        protected ?string $presetsConfig = null
    ) {
    }

    public function get($model, string $key, $value, array $attributes): ImageCollection
    {
        /** @var ImageCollection $imageCollection */
        $imageCollection = app(CastInstanceManager::class)->get($model, $key, true);
        $imageCollection->getImagePrototype()->setPathTemplate($this->pathTemplate);

        if ($value) {
            $imageCollection->setStateFromAttributeData(json_decode($value, true));
        }

        return $imageCollection;
    }

    public function set($model, string $key, $value, array $attributes): ?array
    {
        if (! $value || ($value instanceof ImageCollection && $value->count() === 0 && $value->getAutoincrement() === 1)) {
            return [$key => null];
        }

        return [$key => json_encode($value->getStateAsAttributeData())];
    }
}
