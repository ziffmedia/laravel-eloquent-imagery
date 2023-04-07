<?php

namespace ZiffMedia\LaravelEloquentImagery\Eloquent;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EloquentImageCast implements CastsAttributes
{
    protected array $presets = [];

    public function __construct(protected string $pathTemplate, string $presetsConfig = '')
    {
        if ($presetsConfig) {
            parse_str($presetsConfig, $this->presets);
        }
    }

    public function get($model, string $key, $value, array $attributes): Image
    {
        $image = new Image($this->pathTemplate, $this->presets);

        EloquentImageryObserver::trackModelImage($model, $key, $image);

        if ($value) {
            $image->setStateFromAttributeData(json_decode($value, true));
        }

        return $image;
    }

    public function set($model, string $key, $value, array $attributes): ?array
    {
        if (! $value || ($value instanceof Image && ! $value->exists())) {
            return null;
        }

        $attributes[$key] = json_encode($value->getStateAsAttributeData());

        return $attributes;
    }
}