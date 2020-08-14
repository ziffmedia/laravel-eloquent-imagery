<?php

namespace ZiffMedia\LaravelEloquentImagery\UrlHandler\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;

interface StrategyInterface
{
    public function getDataFromRequest(Request $request): Collection;
    public function toUrl(Image $image, Collection $transformations = null);
}
