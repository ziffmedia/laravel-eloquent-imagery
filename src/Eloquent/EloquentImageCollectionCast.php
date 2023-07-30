<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EloquentImageCollectionCast implements CastsAttributes
{
    protected static CastInstanceManager $castInstanceManager;
    // protected array $presets = [];

    public function __construct(
        protected ?string $pathTemplate = null,
        protected ?string $presetsConfig = null
    ) {
        static::$castInstanceManager ??= app(CastInstanceManager::class);
    }

    public function get($model, string $key, $value, array $attributes): ImageCollection
    {
        /** @var ImageCollection $image */
        $imageCollection = static::$castInstanceManager->get($model, $key, true);

        // @todo
        // if ($this->pathTemplate) {
        //     $imageCollection->setPathTemplate($this->pathTemplate);
        // }

        if ($value) {
            $imageCollection->setStateFromAttributeData(json_decode($value, true));
        }

        return $imageCollection;
    }

    public function set($model, string $key, $value, array $attributes): ?array
    {
        if (! $value || ($value instanceof ImageCollection && $value->count() === 0)) {
            return [];
        }

        return [$key => json_encode($value->getStateAsAttributeData())];
    }
}
