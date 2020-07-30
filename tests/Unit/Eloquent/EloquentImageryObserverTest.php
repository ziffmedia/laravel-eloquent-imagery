<?php

namespace ZiffMedia\LaravelEloquentImagery\Test\Unit\Eloquent;

use ZiffMedia\LaravelEloquentImagery\Eloquent\EloquentImageryObserver;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;
use ZiffMedia\LaravelEloquentImagery\Test\Unit\AbstractTestCase;

class EloquentImageryObserverTest extends AbstractTestCase
{
    public function testRetrievedSetsStateOnImage()
    {
        $foo = new TestAssets\FooModel();
        $foo->setRawAttributes([
            'id' => 1,
            'image' => '{"path": "foo/bar.jpg", "extension": "jpg", "width": 1, "height": 1, "hash": "1234", "timestamp": 12345, "metadata": []}'
        ], true);

        $observer = new EloquentImageryObserver(TestAssets\FooModel::class);
        $observer->retrieved($foo);

        $this->assertInstanceOf(Image::class, $foo->image);
        $this->assertEquals('foo/bar.jpg', $foo->image->toArray()['path']);
    }

    public function testSavingRestoresModelAttributes()
    {
        $foo = new TestAssets\FooModel();
        $foo->image->setStateFromAttributeData([
            'path' => 'foo/bar.jpg',
            'extension' => 'jpg',
            'width' => 1,
            'height' => 1,
            'hash' => '1234',
            'timestamp' => 12345,
            'metadata' => []
        ]);

        $observer = new EloquentImageryObserver(TestAssets\FooModel::class);
        $observer->saving($foo);

        $this->assertEquals('{"index":null,"path":"foo\/bar.jpg","extension":"jpg","width":1,"height":1,"hash":"1234","timestamp":12345,"metadata":[]}', $foo->image);
    }

    public function testSavedRestoresImage()
    {
        $foo = new TestAssets\FooModel();
        $foo->setRawAttributes([
            'id' => 1,
            'image' => '{"path": "foo/bar.jpg", "extension": "jpg", "width": 1, "height": 1, "hash": "1234", "timestamp": 12345, "metadata": []}'
        ], true);

        $observer = new EloquentImageryObserver(TestAssets\FooModel::class);
        $observer->saved($foo);

        $this->assertInstanceOf(Image::class, $foo->image);
    }
}

