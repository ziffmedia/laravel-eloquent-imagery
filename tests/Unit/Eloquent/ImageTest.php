<?php

namespace ZiffMedia\LaravelEloquentImagery\Test\Unit\Eloquent;

use Illuminate\Support\Collection;
use RuntimeException;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;
use ZiffMedia\LaravelEloquentImagery\EloquentImageryProvider;
use ZiffMedia\LaravelEloquentImagery\Test\Unit\AbstractTestCase;

class ImageTest extends AbstractTestCase
{
    protected function getPackageProviders($app)
    {
        return [EloquentImageryProvider::class];
    }

    public function testSetStateFromDataAttribute()
    {
        $image = new Image('foo/{name}.{extension}', []);

        $state = [
            'path'      => 'foo/bar.jpg',
            'extension' => 'jpg',
            'width'     => 1,
            'height'    => 1,
            'hash'      => '1234567890',
            'timestamp' => 12345,
            'metadata'  => []
        ];

        $image->setStateFromAttributeData($state);

        $this->assertEquals('foo/bar.jpg', $image->path);
        $this->assertTrue($image->exists());
        $this->assertEquals('http://localhost/imagery/foo/bar.jpg', $image->url());
    }

    public function testUpdatePath()
    {
        $foo = new TestAssets\FooModel();
        $foo->setRawAttributes(['id' => 20], true);

        $pngImageData = file_get_contents(__DIR__ . '/TestAssets/30.png');

        $image = new Image('foo/{id}.{extension}', []);
        $image->setData($pngImageData);
        $updatedParts = $image->updatePath([], $foo);

        $this->assertEquals('foo/20.png', $image->path);
        $this->assertEquals(['id', 'extension'], $updatedParts);

        $image = new Image('foo/{outside_var}.{extension}', []);
        $image->setData($pngImageData);
        $updatedParts = $image->updatePath(['outside_var' => 'foobar'], $foo);

        $this->assertEquals('foo/foobar.png', $image->path);
        $this->assertEquals(['outside_var', 'extension'], $updatedParts);
    }

    public function testPathHasReplacements()
    {
        $image = new Image('foo/{id}.{extension}', []);
        $image->setData(file_get_contents(__DIR__ . '/TestAssets/30.png'));

        $this->assertTrue($image->pathHasReplacements());

        $image->updatePath(['id' => 5, 'extension' => 'jpg'], new TestAssets\FooModel);

        $this->assertFalse($image->pathHasReplacements());
    }

    public function testMetadata()
    {
        $image = new Image('foo/{name}.{extension}', []);

        $state = [
            'path'      => 'foo/bar.jpg',
            'extension' => 'jpg',
            'width'     => 1,
            'height'    => 1,
            'hash'      => '1234567890',
            'timestamp' => 12345,
            'metadata'  => [
                'one'            => 1,
                'two'            => 2,
                'negative_three' => -3
            ]
        ];

        $image->setStateFromAttributeData($state);

        $this->assertInstanceOf(Collection::class, $image->metadata());
        $this->assertInstanceOf(Collection::class, $image->metadata);

        // assert size
        $this->assertCount(3, $image->metadata);

        // assert a filtered collection can be created
        $this->assertCount(2, $image->metadata->filter(function ($value) { return $value > 0; }));

        // assert the previous filter did not alter the initial collection
        $this->assertCount(3, $image->metadata);

        // assert you can edit the initial collection
        unset($image->metadata['two']);
        $this->assertCount(2, $image->metadata);
    }

    public function testFlush()
    {
        $image = new Image('foo/{name}.{extension}', []);

        $image->setData(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));
        $image->updatePath(['name' => 'bar'], new TestAssets\FooModel);

        $image->flush();

        $this->assertEquals('foo/bar.jpg', $image->path);
        $this->assertFileExists(__DIR__ . '/../../storage/foo/bar.jpg');
    }

    public function testCannotCallSetDataOnReadonlyImage()
    {
        $image = new Image('foo/{name}.{extension}', []);
        $image->setReadOnly();
        $this->expectException(RuntimeException::class);
        $image->setData([]);
    }

    public function testCannotCallRemoveOnReadonlyImage()
    {
        $image = new Image('foo/{name}.{extension}', []);
        $image->setReadOnly();
        $this->expectException(RuntimeException::class);
        $image->remove();
    }

    public function testCannotCallFlushOnReadonlyImage()
    {
        $image = new Image('foo/{name}.{extension}', []);
        $image->setReadOnly();
        $this->expectException(RuntimeException::class);
        $image->flush();
    }
}

