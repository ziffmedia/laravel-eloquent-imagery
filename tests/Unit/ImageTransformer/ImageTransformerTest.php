<?php

namespace ZiffMedia\LaravelEloquentImagery\Test\Unit\ImageTransformer;

use Illuminate\Support\Collection;
use ZiffMedia\LaravelEloquentImagery\ImageTransformer\ImageTransformer;
use ZiffMedia\LaravelEloquentImagery\Test\Unit\AbstractTestCase;

class ImageTransformerTest extends AbstractTestCase
{
    public function testImageTransformerHasTransformations()
    {
        $imageTransformer = new ImageTransformer();

        $this->assertInstanceOf(Collection::class, $imageTransformer->transformations);
        $this->assertCount(5, $imageTransformer->transformations);
    }

    public function testImageTransformerSetsQuality()
    {
        $imageTransformer = new ImageTransformer;

        $bytesOriginal = file_get_contents(__DIR__ . '/picture.jpg');

        $newBytes = $imageTransformer->transform(collect(['quality' => 50]), $bytesOriginal);

        $this->assertLessThan(strlen($bytesOriginal), strlen($newBytes));
    }
}

