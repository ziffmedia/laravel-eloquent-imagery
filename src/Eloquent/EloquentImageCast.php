<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Str;

class EloquentImageCast implements CastsAttributes
{
    public function __construct(
        protected ?string $pathTemplate = null,
        protected ?string $presetsConfig = null
    ) {
    }

    public function get($model, string $key, $value, array $attributes): Image
    {
        /** @var Image $image */
        $image = app(CastInstanceManager::class)->get($model, $key);

        if ($this->pathTemplate) {
            $image->setPathTemplate($this->pathTemplate);
        }

        if ($value) {
            $image->setStateFromAttributeData(json_decode($value, true));
        }

        return $image;
    }

    public function set($model, string $key, $value, array $attributes): ?array
    {
        if (! $value || ($value instanceof Image && ! $value->exists())) {
            return [];
        }

        return [$key => json_encode($value->getStateAsAttributeData())];
    }
}
